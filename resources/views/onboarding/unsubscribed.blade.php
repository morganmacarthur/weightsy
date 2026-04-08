<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weightsy Unsubscribed</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f7f6ef; color: #17212b; }
            .shell { max-width: 720px; margin: 48px auto; padding: 24px; }
            .card { background: #fffdf8; border: 1px solid #e5dccd; border-radius: 18px; padding: 28px; }
            h1 { margin-top: 0; }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="card">
                <h1>You are unsubscribed</h1>
                <p>Weightsy will not send recurring reminder emails to {{ $user->email ?? $user->contactPoints()->value('address') }} unless you opt back in later.</p>
            </section>
        </main>
    </body>
</html>
