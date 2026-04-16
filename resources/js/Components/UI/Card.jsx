import { cn } from '@/lib/utils';

export function Card({ children, className, ...props }) {
    return (
        <div className={cn('bg-white rounded-2xl border border-gray-200 shadow-sm', className)} {...props}>
            {children}
        </div>
    );
}

export function CardHeader({ children, className }) {
    return (
        <div className={cn('px-6 py-4 border-b border-gray-100', className)}>
            {children}
        </div>
    );
}

export function CardBody({ children, className }) {
    return (
        <div className={cn('px-6 py-4', className)}>
            {children}
        </div>
    );
}

export function StatCard({ title, value, icon: Icon, change, changeLabel, color = 'violet', className }) {
    const colors = {
        violet: { bg: 'bg-violet-50', icon: 'bg-violet-100 text-violet-600', value: 'text-violet-600' },
        blue: { bg: 'bg-blue-50', icon: 'bg-blue-100 text-blue-600', value: 'text-blue-600' },
        green: { bg: 'bg-green-50', icon: 'bg-green-100 text-green-600', value: 'text-green-600' },
        orange: { bg: 'bg-orange-50', icon: 'bg-orange-100 text-orange-600', value: 'text-orange-600' },
        red: { bg: 'bg-red-50', icon: 'bg-red-100 text-red-600', value: 'text-red-600' },
    };
    const c = colors[color] || colors.violet;

    return (
        <div className={cn('bg-white rounded-2xl border border-gray-200 p-5 shadow-sm', className)}>
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-sm text-gray-500 font-medium">{title}</p>
                    <p className={cn('text-2xl font-bold mt-1', c.value)}>{value}</p>
                    {(change !== undefined || changeLabel) && (
                        <p className="text-xs text-gray-500 mt-1">
                            {change !== undefined && (
                                <span className={change >= 0 ? 'text-green-600' : 'text-red-600'}>
                                    {change >= 0 ? '↑' : '↓'} {Math.abs(change)}%
                                </span>
                            )}
                            {changeLabel && <span className="ml-1">{changeLabel}</span>}
                        </p>
                    )}
                </div>
                {Icon && (
                    <div className={cn('w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0', c.icon)}>
                        <Icon className="w-6 h-6" />
                    </div>
                )}
            </div>
        </div>
    );
}
