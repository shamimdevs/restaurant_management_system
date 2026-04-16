import { cn } from '@/lib/utils';

const VARIANTS = {
    primary: 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white hover:from-violet-700 hover:to-indigo-700 shadow-sm',
    secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm',
    danger: 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
    ghost: 'text-gray-600 hover:bg-gray-100',
    success: 'bg-green-600 text-white hover:bg-green-700 shadow-sm',
    warning: 'bg-amber-500 text-white hover:bg-amber-600 shadow-sm',
    outline: 'border border-violet-600 text-violet-600 hover:bg-violet-50',
};

const SIZES = {
    xs: 'px-2.5 py-1.5 text-xs rounded-md',
    sm: 'px-3 py-2 text-sm rounded-lg',
    md: 'px-4 py-2.5 text-sm rounded-lg',
    lg: 'px-6 py-3 text-base rounded-xl',
    xl: 'px-8 py-4 text-lg rounded-xl',
};

export default function Button({
    children, variant = 'primary', size = 'md',
    className, disabled, loading, icon: Icon, ...props
}) {
    return (
        <button
            {...props}
            disabled={disabled || loading}
            className={cn(
                'inline-flex items-center justify-center gap-2 font-medium transition-all duration-150',
                'focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2',
                'disabled:opacity-50 disabled:cursor-not-allowed',
                VARIANTS[variant],
                SIZES[size],
                className,
            )}
        >
            {loading ? (
                <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            ) : Icon ? (
                <Icon className="w-4 h-4" />
            ) : null}
            {children}
        </button>
    );
}
