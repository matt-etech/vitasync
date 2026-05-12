@php
    $modal = function (string $id, string $title, string $route, string $body): string {
        return '<div class="modal fade" id="'.$id.'" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form class="modal-content" method="POST" action="'.$route.'">'.csrf_field().'<div class="modal-header"><h2 class="modal-title h5">'.$title.'</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="row g-3">'.$body.'</div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save record</button></div></form></div></div>';
    };

    $clientSelect = fn (): string => $select('client_id', 'Client', $clientOptions);
@endphp

{!! $modal('riskModal', 'Record ongoing risk review', route('safety.risk-reviews.store'),
    $clientSelect().
    $select('risk_domain', 'Risk domain', ['Falls', 'Pressure ulcer', 'Manual handling', 'Environmental', 'Behaviour', 'Safeguarding', 'Medication', 'Nutrition', 'Other']).
    $select('risk_level', 'Risk level', ['None', 'Low', 'Medium', 'High', 'Critical', 'Not assessed']).
    $select('status', 'Status', ['open', 'managed', 'closed'], 'open').
    $input('review_date', 'Review date', 'date', now()->toDateString()).
    $input('next_review_date', 'Next review date', 'date').
    $textarea('hazards', 'Hazards').
    $textarea('control_measures', 'Control measures').
    $textarea('notes', 'Notes')
) !!}

{!! $modal('capacityModal', 'Record capacity review', route('safety.capacity-reviews.store'),
    $clientSelect().
    $select('decision_type', 'Decision type', ['Care package', 'Medication', 'Finances', 'Accommodation', 'Personal care', 'Medical treatment', 'Contact with others', 'Safeguarding', 'Other']).
    $select('capacity_outcome', 'Capacity outcome', ['Has capacity', 'Lacks capacity', 'Fluctuating capacity', 'Unable to assess', 'Needs formal assessment']).
    $select('best_interest_status', 'Best-interest status', ['Not required', 'Required', 'Completed', 'Pending', 'Family consulted', 'Advocate/IMCA required']).
    $select('advocate_status', 'Advocate status', ['Not required', 'Family representative', 'Advocate involved', 'IMCA referral needed', 'IMCA involved']).
    $input('review_date', 'Review date', 'date', now()->toDateString()).
    $input('next_review_date', 'Next review date', 'date').
    $textarea('evidence', 'Evidence').
    $textarea('notes', 'Notes')
) !!}

{!! $modal('consentModal', 'Record client consent', route('safety.consent-records.store'),
    $clientSelect().
    $select('consent_type', 'Consent type', ['Care delivery', 'Medication support', 'Information sharing', 'Photo evidence', 'Family contact', 'External referral', 'Other']).
    $select('decision', 'Decision', ['Consented', 'Declined', 'Withdrawn', 'Best-interest decision', 'Unable to decide']).
    $select('given_by', 'Given by', ['Client', 'Representative', 'Attorney/deputy', 'Best-interest process', 'Not applicable']).
    $input('recorded_at', 'Recorded at', 'datetime-local', now()->format('Y-m-d\TH:i')).
    $input('review_date', 'Review date', 'date').
    $textarea('evidence', 'Evidence').
    $textarea('notes', 'Notes')
) !!}

{!! $modal('medicationModal', 'Add medication to MAR', route('safety.medications.store'),
    $clientSelect().
    $input('name', 'Medication name').
    $input('dose', 'Dose').
    $select('route', 'Route', ['Oral', 'Topical', 'Inhaled', 'Eye drops', 'Ear drops', 'Injection', 'Patch', 'Other']).
    $input('frequency', 'Frequency').
    $select('support_level', 'Support level', ['Self-administers', 'Prompting', 'Assistance required', 'Administration by carer', 'MAR chart required', 'District nurse support'], 'Prompting').
    $select('status', 'Status', ['active', 'paused', 'stopped'], 'active').
    $input('start_date', 'Start date', 'date').
    $input('end_date', 'End date', 'date').
    $textarea('instructions', 'Instructions')
) !!}

{!! $modal('medAdminModal', 'Record medication administration', route('safety.medication-administrations.store'),
    $select('medication_id', 'Medication', $medications->where('status', 'active')->map(fn ($medication) => ['id' => $medication->id, 'label' => $medication->client->fullName().' - '.$medication->name.' '.$medication->dose])).
    $select('visit_id', 'Linked visit', $visits->map(fn ($visit) => ['id' => $visit->id, 'label' => $visit->client->fullName().' - '.$visit->scheduled_start_at?->format('d/m/Y H:i')])).
    $select('outcome', 'Outcome', ['Administered', 'Prompted', 'Refused', 'Not available', 'Withheld', 'Self-administered', 'Error reported']).
    $input('administered_at', 'Administered at', 'datetime-local', now()->format('Y-m-d\TH:i')).
    $textarea('notes', 'Notes')
) !!}

{!! $modal('incidentModal', 'Record incident', route('safety.incidents.store'),
    $clientSelect().
    $select('visit_id', 'Linked visit', $visits->map(fn ($visit) => ['id' => $visit->id, 'label' => $visit->client->fullName().' - '.$visit->scheduled_start_at?->format('d/m/Y H:i')])).
    $select('category', 'Category', ['Fall', 'Medication', 'Behaviour', 'Injury', 'Safeguarding', 'Missing person', 'Property/environment', 'Other']).
    $select('severity', 'Severity', ['Info', 'Low', 'Medium', 'High', 'Critical']).
    $select('status', 'Status', ['open', 'investigating', 'closed'], 'open').
    $input('occurred_at', 'Occurred at', 'datetime-local', now()->format('Y-m-d\TH:i')).
    '<div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" id="safeguarding_required" name="safeguarding_required" type="checkbox" value="1"><label class="form-check-label fw-semibold" for="safeguarding_required">Safeguarding escalation required</label></div></div>'.
    $textarea('description', 'Description').
    $textarea('immediate_actions', 'Immediate actions')
) !!}

{!! $modal('safeguardingModal', 'Open safeguarding case', route('safety.safeguarding-cases.store'),
    $clientSelect().
    $select('incident_id', 'Linked incident', $incidents->map(fn ($incident) => ['id' => $incident->id, 'label' => $incident->client->fullName().' - '.$incident->category.' '.$incident->occurred_at?->format('d/m/Y H:i')])).
    $select('concern_type', 'Concern type', ['Neglect', 'Physical abuse', 'Emotional abuse', 'Financial abuse', 'Medication concern', 'Self-neglect', 'Domestic abuse', 'Organisational abuse', 'Other']).
    $select('risk_level', 'Risk level', ['Low', 'Medium', 'High', 'Critical']).
    $select('status', 'Status', ['open', 'referred', 'monitoring', 'closed'], 'open').
    $input('opened_at', 'Opened at', 'datetime-local', now()->format('Y-m-d\TH:i')).
    $input('referred_at', 'Referred at', 'datetime-local').
    $textarea('summary', 'Summary').
    $textarea('actions_taken', 'Actions taken')
) !!}
