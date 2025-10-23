import clsx from 'clsx';
import { getStatusClasses, statusLabel } from '../utils/statusStyles.js';

export const StatusBadge = ({ status = 'info', children }) => (
  <span
    className={clsx(
      'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium capitalize shadow-sm transition-colors',
      getStatusClasses(status)
    )}
  >
    {children || statusLabel[status] || status}
  </span>
);
