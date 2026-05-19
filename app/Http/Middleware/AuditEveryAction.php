<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditEveryAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actorId = $this->actorId($request);
        $actorName = $this->friendlyActor($request);
        $response = $next($request);

        if ($this->shouldAudit($request)) {
            $this->auditLogger->log('system.action_performed', [
                'actor_id' => $actorId,
                'event' => 'System action',
                'friendly_action' => $this->friendlyVerb($request),
                'friendly_subject' => $this->friendlySubject($request),
                'friendly_actor' => $actorName,
                'friendly_summary' => $this->friendlySummary($request, $response, $actorName),
                'metadata' => [
                    'Action type' => $request->isMethod('GET') ? 'Viewed information' : 'Changed or submitted information',
                    'Screen or feature' => $this->friendlySubject($request),
                    'Result' => $this->friendlyResult($response),
                    'Route' => $request->route()?->getName(),
                    'Page address' => $request->path(),
                    'Status code' => $response->getStatusCode(),
                    'Family member ID' => $request->input('family_member_id'),
                    'Carer ID' => $request->input('carer_id'),
                    'Client ID' => $request->input('client_id'),
                    'Visit ID' => $this->routeRecordId($request, 'visit'),
                ],
            ]);
        }

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        return $request->route() !== null
            && ! $request->is('up');
    }

    private function actorId(Request $request): ?int
    {
        if (Auth::id()) {
            return Auth::id();
        }

        return $request->integer('carer_id') ?: null;
    }

    private function friendlyActor(Request $request): string
    {
        if (Auth::user()) {
            return Auth::user()->name;
        }

        if ($request->filled('carer_id')) {
            return 'Carer #'.$request->integer('carer_id');
        }

        if ($request->filled('family_member_id')) {
            return 'Family member #'.$request->integer('family_member_id');
        }

        return 'Someone';
    }

    private function friendlyVerb(Request $request): string
    {
        return match ($request->method()) {
            'GET', 'HEAD' => 'Viewed',
            'POST' => 'Submitted',
            'PUT', 'PATCH' => 'Updated',
            'DELETE' => 'Deleted or disabled',
            default => 'Used',
        };
    }

    private function friendlySubject(Request $request): string
    {
        $routeName = $request->route()?->getName();
        $raw = $routeName ?: $request->path();

        return Str::of($raw)
            ->replace(['api.', '.', '_', '-', '/'], ' ')
            ->replaceMatches('/\{[^}]+\}/', '')
            ->squish()
            ->title()
            ->toString();
    }

    private function friendlyResult(Response $response): string
    {
        if ($response->isSuccessful()) {
            return 'Completed successfully';
        }

        if ($response->isRedirection()) {
            return 'Completed and moved to another page';
        }

        return 'Did not complete successfully';
    }

    private function friendlySummary(Request $request, Response $response, string $actorName): string
    {
        return sprintf(
            '%s %s %s. Result: %s.',
            $actorName,
            Str::lower($this->friendlyVerb($request)),
            $this->friendlySubject($request),
            $this->friendlyResult($response),
        );
    }

    private function routeRecordId(Request $request, string $key): mixed
    {
        $value = $request->route($key);

        return $value instanceof Model ? $value->getKey() : $value;
    }
}
