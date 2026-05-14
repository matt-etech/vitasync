import './bootstrap';

const parseJsonData = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
};

const parseDateTime = (value) => {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
};

const sameWeek = (left, right) => {
    const weekStart = (date) => {
        const copy = new Date(date);
        const day = copy.getDay() || 7;
        copy.setHours(0, 0, 0, 0);
        copy.setDate(copy.getDate() - day + 1);

        return copy.getTime();
    };

    return weekStart(left) === weekStart(right);
};

const hoursBetween = (start, end) => (end.getTime() - start.getTime()) / 36e5;

const isNightShift = (start, end) => start.getHours() < 8 || end.getHours() > 20 || start.toDateString() !== end.toDateString();

const workerAssignmentIssues = (option, clientHomeId, scheduledStart, scheduledEnd, currentVisitId) => {
    const issues = [];
    const complianceReasons = parseJsonData(option.dataset.complianceReasons, []);
    const workerHomeId = option.dataset.homeId;
    const shiftPreference = option.dataset.shiftPreference;
    const maxWeeklyHours = Number(option.dataset.maxWeeklyHours || 0);
    const existingVisits = parseJsonData(option.dataset.existingVisits, []);

    if (option.dataset.complianceReady !== '1') {
        issues.push(...complianceReasons);
    }

    if (clientHomeId && workerHomeId && String(clientHomeId) !== String(workerHomeId)) {
        issues.push('Worker is assigned to a different home.');
    }

    if (scheduledStart && scheduledEnd) {
        if (shiftPreference === 'day' && isNightShift(scheduledStart, scheduledEnd)) {
            issues.push('Worker is not available for this shift time.');
        }

        if (shiftPreference === 'night' && !isNightShift(scheduledStart, scheduledEnd)) {
            issues.push('Worker is not available for this shift time.');
        }

        const newVisitHours = hoursBetween(scheduledStart, scheduledEnd);
        let weeklyHours = newVisitHours;

        for (const visit of existingVisits) {
            if (String(visit.id) === String(currentVisitId)) {
                continue;
            }

            const visitStart = parseDateTime(visit.start);
            const visitEnd = parseDateTime(visit.end);

            if (!visitStart || !visitEnd) {
                continue;
            }

            if (visitStart < scheduledEnd && visitEnd > scheduledStart) {
                issues.push('Worker already has a visit during this time.');
            }

            if (sameWeek(visitStart, scheduledStart)) {
                weeklyHours += hoursBetween(visitStart, visitEnd);
            }
        }

        if (maxWeeklyHours > 0 && weeklyHours > maxWeeklyHours) {
            issues.push('Worker would exceed their recorded weekly availability.');
        }
    }

    return [...new Set(issues.filter(Boolean))];
};

const refreshVisitWorkerSelect = (form) => {
    const workerSelect = form.querySelector('[data-visit-worker-select]');
    const clientSelect = form.querySelector('[name="client_id"]');
    const startInput = form.querySelector('[name="scheduled_start_at"]');
    const endInput = form.querySelector('[name="scheduled_end_at"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const guidance = form.querySelector('[data-visit-worker-guidance]');

    if (!workerSelect || !clientSelect || !startInput || !endInput || !guidance) {
        return;
    }

    const selectedClient = clientSelect.selectedOptions[0];
    const clientHomeId = selectedClient?.dataset.homeId || '';
    const scheduledStart = parseDateTime(startInput.value);
    const scheduledEnd = parseDateTime(endInput.value);
    const currentVisitId = workerSelect.dataset.currentVisitId;
    const selectedOption = workerSelect.selectedOptions[0];
    let selectedIssues = [];

    for (const option of workerSelect.options) {
        if (!option.value) {
            continue;
        }

        const issues = workerAssignmentIssues(option, clientHomeId, scheduledStart, scheduledEnd, currentVisitId);
        option.disabled = issues.length > 0;
        option.dataset.assignmentIssues = JSON.stringify(issues);
        option.textContent = option.textContent.replace(/\s+\((Blocked|Eligible)\)$/, '');
        option.textContent += issues.length > 0 ? ' (Blocked)' : ' (Eligible)';

        if (option === selectedOption) {
            selectedIssues = issues;
        }
    }

    if (selectedOption?.value && selectedIssues.length > 0) {
        guidance.classList.remove('text-secondary');
        guidance.classList.add('text-danger', 'fw-semibold');
        guidance.textContent = selectedIssues.join(' ');
        workerSelect.setCustomValidity(selectedIssues.join(' '));
    } else {
        guidance.classList.remove('text-danger', 'fw-semibold');
        guidance.classList.add('text-secondary');
        guidance.textContent = selectedOption?.value
            ? 'Worker is compliant and available for this selected visit window.'
            : 'Select a client and visit window to check worker compliance and availability.';
        workerSelect.setCustomValidity('');
    }

    if (submitButton) {
        submitButton.disabled = Boolean(selectedOption?.value && selectedIssues.length > 0);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    for (const workerSelect of document.querySelectorAll('[data-visit-worker-select]')) {
        const form = workerSelect.closest('form');

        if (!form) {
            continue;
        }

        refreshVisitWorkerSelect(form);

        for (const field of ['client_id', 'scheduled_start_at', 'scheduled_end_at', 'assigned_user_id']) {
            form.querySelector(`[name="${field}"]`)?.addEventListener('change', () => refreshVisitWorkerSelect(form));
        }
    }
});
