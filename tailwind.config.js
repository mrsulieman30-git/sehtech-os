/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.ts',
    ],
    theme: {
        extend: {
            colors: {
                shell: {
                    bg: '#0F172A',
                    window: '#1E293B',
                    panel: '#F8FAFC',
                    border: '#E2E8F0',
                },
                text: {
                    primary: '#0F172A',
                    secondary: '#475569',
                    disabled: '#94A3B8',
                },
                state: {
                    focus: '#2563EB',
                    success: '#059669',
                    warning: '#CA8A04',
                    error: '#DC2626',
                    info: '#0891B2',
                },
                dept: {
                    admin: { main: '#1B2A4A', sec: '#F1F5F9' },
                    research: { main: '#4338CA', sec: '#EEF2FF' },
                    dev: { main: '#0F172A', sec: '#1D4ED8' },
                    marketing: { main: '#DB2777', sec: '#FDF2F8' },
                    sales: { main: '#059669', sec: '#ECFDF5' },
                    legal: { main: '#7C3AED', sec: '#F5F3FF' },
                    finance: { main: '#CA8A04', sec: '#FFFBEB' },
                    hr: { main: '#0891B2', sec: '#ECFEFF' },
                    support: { main: '#EA580C', sec: '#FFF7ED' },
                    ops: { main: '#64748B', sec: '#F1F5F9' },
                    ai: { main: '#0D9488', sec: '#F0FDFA' },
                    portal: { main: '#2563EB', sec: '#EFF6FF' }
                }
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                arabic: ['Cairo', 'sans-serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            borderRadius: {
                btn: '10px',
                card: '12px',
                modal: '16px',
                input: '10px',
                tag: '20px',
                window: '14px',
                app: '12px',
            },
            boxShadow: {
                card: '0 2px 8px rgba(0,0,0,0.08)',
                modal: '0 20px 60px rgba(0,0,0,0.25)',
                dock: '0 -4px 40px rgba(0,0,0,0.4)',
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
