import { cn } from '@/lib/utils';
import { forwardRef } from 'react';
import { ChevronDown } from 'lucide-react';

const Select = forwardRef(function Select({
    label, error, options = [], className, containerClass, placeholder, ...props
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
                <select
                    ref={ref}
                    {...props}
                    className={cn(
                        'w-full rounded-lg border bg-white text-sm text-gray-900 appearance-none',
                        'transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500',
                        'pr-9 pl-3 py-2.5',
                        error
                            ? 'border-red-300 focus:border-red-400 focus:ring-red-200'
                            : 'border-gray-300 focus:border-violet-400',
                        'disabled:bg-gray-50 disabled:cursor-not-allowed',
                        className,
                    )}
                >
                    {placeholder && <option value="">{placeholder}</option>}
                    {options.map(opt => (
                        typeof opt === 'string'
                            ? <option key={opt} value={opt}>{opt}</option>
                            : <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
                <ChevronDown className="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
            </div>
            {error && <p className="text-xs text-red-600">{error}</p>}
        </div>
    );
});

export default Select;
