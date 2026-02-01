/**
 * Форматирует дату YYYY-MM-DD в локальный формат браузера.
 * Создается локальная полуночь, чтобы избежать UTC-сдвигов.
 */
export function formatLocalDate(ymd) {
  if (!ymd) return '';

  const dt = new Date(ymd + 'T00:00:00');

  return new Intl.DateTimeFormat(
    navigator.language,
    {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }
  ).format(dt);
}