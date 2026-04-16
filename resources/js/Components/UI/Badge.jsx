import { cn, badgeClass } from '@/lib/utils';

export default function Badge({ status, children, className }) {
    return (
        <span className={cn(
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize',
            badgeClass(status),
            className,
        )}>
            {children || status}
        </span>
    );
}
