<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('inventory.landing.title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-surface: #ffffff;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
            --color-primary: #4f46e5;
            --color-primary-light: #e0e7ff;
            --color-border: #e2e8f0;
            --color-accent-green: #059669;
            --color-accent-amber: #d97706;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --color-bg: #0f172a;
                --color-surface: #1e293b;
                --color-text: #f1f5f9;
                --color-text-muted: #94a3b8;
                --color-primary: #818cf8;
                --color-primary-light: #1e1b4b;
                --color-border: #334155;
                --color-accent-green: #34d399;
                --color-accent-amber: #fbbf24;
            }
        }
        :root[data-theme="dark"] {
            --color-bg: #0f172a;
            --color-surface: #1e293b;
            --color-text: #f1f5f9;
            --color-text-muted: #94a3b8;
            --color-primary: #818cf8;
            --color-primary-light: #1e1b4b;
            --color-border: #334155;
            --color-accent-green: #34d399;
            --color-accent-amber: #fbbf24;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Noto Sans Arabic', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Header */
        header {
            padding: 20px 0;
            border-bottom: 1px solid var(--color-border);
        }
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--color-primary);
            text-decoration: none;
        }
        .brand svg { width: 32px; height: 32px; }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.15s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--color-primary);
            color: #fff;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-ghost {
            background: transparent;
            color: var(--color-text-muted);
            border: 1px solid var(--color-border);
        }
        .btn-ghost:hover { background: var(--color-surface); }

        /* Hero */
        .hero {
            padding: 80px 0 60px;
            text-align: center;
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }
        .hero p {
            font-size: 1.125rem;
            color: var(--color-text-muted);
            max-width: 640px;
            margin: 0 auto 32px;
        }

        /* Features */
        .features-heading {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 40px;
            color: var(--color-text);
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            padding-bottom: 80px;
        }
        .feature-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 28px;
            transition: box-shadow 0.2s;
        }
        .feature-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            background: var(--color-primary-light);
            color: var(--color-primary);
        }
        .feature-icon svg { width: 20px; height: 20px; }
        .feature-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            line-height: 1.7;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--color-border);
            padding: 24px 0;
            text-align: center;
            font-size: 0.8rem;
            color: var(--color-text-muted);
        }
        footer span { font-weight: 500; }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="/" class="brand">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                MultiBranch Inventory
            </a>
            <div class="header-actions">
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="btn btn-ghost">
                    {{ __('inventory.landing.switch_lang') }}
                </a>
                @if (Route::has('login'))
                    <a href="{{ route('filament.admin.auth.login') }}" class="btn btn-primary">
                        {{ __('inventory.landing.login') }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <h1>{{ __('inventory.landing.subtitle') }}</h1>
            <p>{{ __('inventory.landing.description') }}</p>
            @if (Route::has('login'))
                <a href="{{ route('filament.admin.auth.login') }}" class="btn btn-primary" style="font-size: 1rem; padding: 12px 32px;">
                    {{ __('inventory.landing.login') }}
                </a>
            @endif
        </section>

        <h2 class="features-heading">{{ __('inventory.landing.features') }}</h2>

        <div class="features-grid">
            {{-- Stock Tracking --}}
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_stock_title') }}</h3>
                <p>{{ __('inventory.landing.feature_stock_desc') }}</p>
            </div>

            {{-- Transfers --}}
            <div class="feature-card">
                <div class="feature-icon" style="background: #fef3c7; color: #d97706;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_transfer_title') }}</h3>
                <p>{{ __('inventory.landing.feature_transfer_desc') }}</p>
            </div>

            {{-- Reorder --}}
            <div class="feature-card">
                <div class="feature-icon" style="background: #d1fae5; color: #059669;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_reorder_title') }}</h3>
                <p>{{ __('inventory.landing.feature_reorder_desc') }}</p>
            </div>

            {{-- Revenue Reports --}}
            <div class="feature-card">
                <div class="feature-icon" style="background: #e0f2fe; color: #0284c7;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_reports_title') }}</h3>
                <p>{{ __('inventory.landing.feature_reports_desc') }}</p>
            </div>

            {{-- Roles --}}
            <div class="feature-card">
                <div class="feature-icon" style="background: #fce7f3; color: #db2777;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_roles_title') }}</h3>
                <p>{{ __('inventory.landing.feature_roles_desc') }}</p>
            </div>

            {{-- Stock Movements --}}
            <div class="feature-card">
                <div class="feature-icon" style="background: #f3e8ff; color: #7c3aed;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                </div>
                <h3>{{ __('inventory.landing.feature_movements_title') }}</h3>
                <p>{{ __('inventory.landing.feature_movements_desc') }}</p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            {{ __('inventory.landing.built_with') }}
            <span>Laravel</span> &middot; <span>Filament</span> &middot; <span>Spatie Permission</span>
        </div>
    </footer>
</body>
</html>
