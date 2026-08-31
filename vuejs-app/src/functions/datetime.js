import moment from 'moment';

/**
 * Convert UTC datetime from backend to local timezone
 * Backend always sends UTC, so we parse as UTC and convert to local
 */
export function utcToLocal(utcDateTime) {
  if (!utcDateTime) return null;
  // Parse as UTC and convert to local timezone
  return moment.utc(utcDateTime).local();
}

/**
 * Convert local datetime to UTC for sending to backend
 * Takes local time and converts to UTC string
 */
export function localToUtc(localDateTime) {
  if (!localDateTime) return null;
  // Parse as local and convert to UTC
  return moment(localDateTime).utc().format('YYYY-MM-DD HH:mm:ss');
}

/**
 * Format chat timestamp for display
 * Shows relative time (5 min ago), or date for older messages
 * Expects UTC datetime from backend
 */
export function formatChatTime(dateTime) {
  if (!dateTime) return '';

  const now = moment();
  const msgTime = moment(dateTime);
  const diffMinutes = now.diff(msgTime, 'minutes');
  const diffHours = now.diff(msgTime, 'hours');
  const diffDays = now.diff(msgTime, 'days');

  // Less than 1 minute
  if (diffMinutes < 1) return 'now';

  // Less than 1 hour
  if (diffHours < 1) return `${diffMinutes} min ago`;

  // Today
  if (diffDays === 0) return msgTime.format('HH:mm');

  // Yesterday
  if (diffDays === 1) return 'Yesterday';

  // Within last week
  if (diffDays < 7) return msgTime.format('ddd');

  // Older messages
  return msgTime.format('DD/MM/YY');
}
