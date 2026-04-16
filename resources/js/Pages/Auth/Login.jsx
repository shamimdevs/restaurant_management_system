import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { UtensilsCrossed, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';

export default function Login({ errors: serverErrors }) {
    const [showPw, setShowPw] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-900 via-purple-900 to-indigo-900 p-4">
            {/* Decorative blobs */}
            <div className="absolute top-20 left-20 w-72 h-72 bg-white/5 rounded-full blur-3xl" />
            <div className="absolute bottom-20 right-20 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl" />

            <div className="relative w-full max-w-md">
                {/* Logo */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 backdrop-blur mb-4">
                        <UtensilsCrossed className="w-9 h-9 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold text-white">Restaurant Manager</h1>
                    <p className="text-purple-300 text-sm mt-1">Sign in to your account</p>
                </div>

                {/* Card */}
                <div className="bg-white rounded-3xl shadow-2xl p-8">
                    <form onSubmit={submit} className="space-y-5">
                        <Input
                            label="Email"
                            type="email"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                            error={errors.email}
                            icon={Mail}
                            placeholder="admin@restaurant.com"
                            required
                            autoFocus
                        />

                        <div>
                            <Input
                                label="Password"
                                type={showPw ? 'text' : 'password'}
                                value={data.password}
                                onChange={e => setData('password', e.target.value)}
                                error={errors.password}
                                icon={Lock}
                                placeholder="••••••••"
                                required
                            />
                            <button
                                type="button"
                                onClick={() => setShowPw(!showPw)}
                                className="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                                style={{ position: 'relative', marginTop: '-2.2rem', float: 'right', marginRight: '0.75rem' }}
                            >
                                {showPw ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>

                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={e => setData('remember', e.target.checked)}
                                className="w-4 h-4 rounded text-violet-600 border-gray-300 focus:ring-violet-500"
                            />
                            <span className="text-sm text-gray-600">Remember me</span>
                        </label>

                        {serverErrors?.message && (
                            <div className="bg-red-50 text-red-700 text-sm px-4 py-3 rounded-xl">
                                {serverErrors.message}
                            </div>
                        )}

                        <Button type="submit" className="w-full" size="lg" loading={processing}>
                            Sign In
                        </Button>
                    </form>

                    {/* PIN login hint */}
                    <p className="text-center text-xs text-gray-400 mt-6">
                        For POS quick login, use PIN from the POS screen
                    </p>
                </div>
            </div>
        </div>
    );
}
