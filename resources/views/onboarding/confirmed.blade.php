<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weightsy Confirmed</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f7f6ef; color: #17212b; }
            .shell { max-width: 720px; margin: 48px auto; padding: 24px; }
            .card { background: #fffdf8; border: 1px solid #e5dccd; border-radius: 18px; padding: 28px; }
            h1 { margin-top: 0; }
            a { color: #0d5c7d; }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="card">
                <h1>Reminders confirmed</h1>
                <p>Weightsy will send daily reminder emails to {{ $user->email ?? $user->contactPoints()->value('address') }} around {{ substr((string) $user->reminder_time_local, 0, 5) }} in {{ $user->timezone }}.</p>
                <p><a href="{{ route('home') }}">Back to Weightsy</a></p>
            </section>
        </main>
    </body>
</html>
