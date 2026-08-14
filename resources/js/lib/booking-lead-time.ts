export type BookingRules = {
    minimum_value: number;
    minimum_unit: 'hours' | 'days';
    earliest_visit_at: string;
    message: string;
};

export function minimumBookingDate(rules: BookingRules): string {
    return rules.earliest_visit_at.slice(0, 10);
}

export function isVisitTimeAllowed(rules: BookingRules, date: string, startTime: string): boolean {
    const visitAt = new Date(`${date}T${startTime}:00+07:00`).getTime();
    const earliestAt = new Date(rules.earliest_visit_at).getTime();

    return Number.isFinite(visitAt) && Number.isFinite(earliestAt) && visitAt >= earliestAt;
}

export function dateHasAllowedTime(
    rules: BookingRules,
    date: string,
    timeSlots: Array<{ start_time: string }>,
): boolean {
    return timeSlots.some((slot) => isVisitTimeAllowed(rules, date, slot.start_time));
}
