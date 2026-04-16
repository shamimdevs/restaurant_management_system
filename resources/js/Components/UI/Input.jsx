import { cn } from '@/lib/utils';
import { forwardRef } from 'react';

const Input = forwardRef(function Input({
    label, error, hint, className, containerClass, icon: Icon, ...props
}, ref) {
    return (
        <div className={cn('flex flex-col gap-1', containerClass)}>
            {label && (
                <label className="text-sm font-medium text-gray-700">
                    {label}
                    {props.required && <span className="text-red-500 ml-0.5">*</span>}
                </label>
            )}
            <div className="relative">
                {Icon && (
                    <div className="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <Icon className="w-4 h-4 text-gray-400" />
                    </div>
                )}
                <input
                    ref={ref}
                    {...props}
                    className={cn(
                        'w-full rounded-lg border bg-white text-sm text-gray-900 placeholder-gray-400',
                        'transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500',
                        error
                            ? 'border-red-300 focus:border-red-400 focus:ring-red-200'
                            : 'border-gray-300 focus:border-violet-400',
                        Icon ? 'pl-9 pr-3 py-2.5' : 'px-3 py-2.5',
                        'disabled:bg-gray-50 disabled:cursor-not-allowed',
                        className,
                    )}
                />
            </div>
            {error && <p className="text-xs text-red-600">{error}</p>}
            {hint && !error && <p className="text-xs text-gray-500">{hint}</p>}
        </div>
    );
});

export default Input;
