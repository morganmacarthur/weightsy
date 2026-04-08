<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weightsy</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|source-serif-4:400,600" rel="stylesheet" />
        <style>
            :root {
                --ink: #10212f;
                --sky: #d9eef8;
                --sea: #7bb7cf;
                --sand: #f6f1df;
                --peach: #f4a77f;
                --rose: #cf6679;
                --panel: rgba(255, 252, 247, 0.78);
                --line: rgba(16, 33, 47, 0.12);
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                font-family: "Space Grotesk", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(123, 183, 207, 0.8), transparent 38%),
                    radial-gradient(circle at right 20%, rgba(244, 167, 127, 0.75), transparent 28%),
                    linear-gradient(180deg, #f7fbfc 0%, #f7f4ea 100%);
            }
            .shell { max-width: 1140px; margin: 0 auto; padding: 32px 20px 64px; }
            .hero { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 28px; align-items: stretch; }
            .card {
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--panel);
                backdrop-filter: blur(12px);
                box-shadow: 0 24px 60px rgba(16, 33, 47, 0.08);
            }
            .hero-copy { padding: 38px; }
            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(16, 33, 47, 0.06);
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            h1 { margin: 18px 0 16px; font-size: clamp(2.9rem, 8vw, 5.8rem); line-height: 0.92; letter-spacing: -0.06em; }
            .lede { max-width: 42rem; font-size: 1.1rem; line-height: 1.7; }
            .stack { display: grid; gap: 16px; margin-top: 28px; }
            .chip-grid { display: flex; flex-wrap: wrap; gap: 12px; }
            .chip { padding: 12px 16px; border-radius: 16px; background: rgba(255, 255, 255, 0.88); border: 1px solid rgba(16, 33, 47, 0.08); font-size: 0.95rem; }
            .hero-panel { padding: 28px; overflow: hidden; }
            .graph { position: relative; min-height: 100%; padding: 24px; border-radius: 24px; background: linear-gradient(180deg, rgba(217, 238, 248, 0.95), rgba(246, 241, 223, 0.95)), #fff; }
            .graph svg { width: 100%; height: 100%; min-height: 360px; }
            .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: 22px; }
            .mini { padding: 24px; }
            h2 { margin: 0 0 10px; font-size: 1.3rem; }
            p { margin: 0; line-height: 1.7; }
            .mono {
                margin-top: 16px;
                padding: 12px 14px;
                border-radius: 14px;
                background: rgba(16, 33, 47, 0.92);
                color: #f7fbfc;
                font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
                font-size: 0.95rem;
            }
            .steps { display: grid; gap: 14px; margin-top: 16px; }
            .step { padding: 16px 18px; border-radius: 18px; background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(16, 33, 47, 0.08); }
            .step strong { display: block; margin-bottom: 6px; }
            .footer-note { margin-top: 22px; font-size: 0.95rem; opacity: 0.82; }
            @media (max-width: 900px) {
                .hero, .grid { grid-template-columns: 1fr; }
                .hero-copy, .hero-panel { padding: 24px; }
            }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="hero">
                <div class="card hero-copy">
                    <span class="eyebrow">message-first tracking</span>
                    <h1>One reply.<br>Three signals.<br>One graph.</h1>
                    <p class="lede">Weightsy is being rebuilt around the simplest possible daily habit: receive a reminder, reply with your number, and let the site organize weight, body fat, and blood pressure into one longer-term timeline.</p>
                    <div class="stack">
                        <div class="chip-grid">
                            <span class="chip">weight: <strong>123</strong></span>
                            <span class="chip">blood pressure: <strong>120/70</strong></span>
                            <span class="chip">body fat: <strong>14.0%</strong></span>
                        </div>
                        <div class="mono">{{ $checkinAddress }}</div>
                    </div>
                </div>
                <div class="card hero-panel">
                    <div class="graph">
                        <svg viewBox="0 0 520 360" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M40 36H490" stroke="rgba(16,33,47,0.12)" />
                            <path d="M40 116H490" stroke="rgba(16,33,47,0.12)" />
                            <path d="M40 196H490" stroke="rgba(16,33,47,0.12)" />
                            <path d="M40 276H490" stroke="rgba(16,33,47,0.12)" />
                            <path d="M40 324H490" stroke="rgba(16,33,47,0.2)" />
                            <path d="M52 230C96 204 113 212 152 176C192 139 212 146 252 124C290 104 315 109 352 92C393 73 424 82 476 50" stroke="#10212F" stroke-width="6" stroke-linecap="round"/>
                            <path d="M52 262C95 248 120 231 152 239C198 250 211 220 252 215C292 210 311 187 352 179C395 169 433 170 476 142" stroke="#CF6679" stroke-width="6" stroke-linecap="round"/>
                            <path d="M52 291C94 286 119 297 152 283C191 266 217 275 252 260C291 244 321 248 352 229C392 204 430 214 476 186" stroke="#F4A77F" stroke-width="6" stroke-linecap="round"/>
                            <circle cx="152" cy="176" r="7" fill="#10212F"/>
                            <circle cx="252" cy="124" r="7" fill="#10212F"/>
                            <circle cx="352" cy="179" r="7" fill="#CF6679"/>
                            <circle cx="476" cy="186" r="7" fill="#F4A77F"/>
                        </svg>
                    </div>
                </div>
            </section>
            <section class="grid">
                <article class="card mini">
                    <h2>How signup works</h2>
                    <p>Your first message creates the account. We keep the sending address as the primary reply target, mark the first check-in as recorded, and schedule the next daily reminder at that same local time.</p>
                </article>
                <article class="card mini">
                    <h2>How login works</h2>
                    <p>The default product stays in messaging, but the web app will use one-time passwords and magic links when someone wants to explore a longer timeline or edit entries manually.</p>
                </article>
                <article class="card mini">
                    <h2>What exists now</h2>
                    <p>The Laravel restart already has SQLite, the message parser, an inbound check-in endpoint, and tables for check-ins, reminders, login tokens, contact points, and message history.</p>
                </article>
            </section>
            <section class="card mini" style="margin-top: 22px;">
                <h2>Inbound flow</h2>
                <div class="steps">
                    <div class="step">
                        <strong>1. Incoming message arrives</strong>
                        We accept a sender address plus plain text payload through <code>POST /inbound/checkins</code>.
                    </div>
                    <div class="step">
                        <strong>2. Message gets parsed</strong>
                        The parser currently understands `123`, `120/70`, and `14.0%`.
                    </div>
                    <div class="step">
                        <strong>3. Data is recorded</strong>
                        A user, contact point, check-in record, inbound message, and reminder schedule are created or updated in one transaction.
                    </div>
                </div>
                <p class="footer-note">The next build steps are the actual outbound reminder sender, confirmation reply with a small graph image, and the OTP + magic-link web experience.</p>
            </section>
        </main>
    </body>
</html>
