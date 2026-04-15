<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weightsy Reminder Settings</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f7f6ef; color: #17212b; }
            .shell { max-width: 720px; margin: 48px auto; padding: 24px; }
            .card { background: #fffdf8; border: 1px solid #e5dccd; border-radius: 18px; padding: 28px; }
            h1 { margin-top: 0; }
            label, input, select, button { display: block; width: 100%; }
            input, select { margin: 10px 0 16px; padding: 10px 12px; font-size: 16px; }
            button { background: #0d5c7d; color: white; border: 0; border-radius: 12px; padding: 12px 14px; cursor: pointer; }
            .status { margin-bottom: 16px; padding: 10px 12px; background: #eef8ec; border-radius: 12px; }
            .links { margin-top: 16px; }
            a { color: #0d5c7d; }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="card">
                <h1>Choose your reminder time</h1>
                <p>Set the daily email reminder time that feels easiest to stick with. Saving this also confirms that you want Weightsy reminders.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <form method="post" action="{{ route('onboarding.update', ['user' => $user] + request()->query()) }}">
                    @csrf
                    <label for="timezone">Timezone</label>
                    <select id="timezone" name="timezone" required>
                        @foreach ($timezoneOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('timezone', $user->timezone) === $value)>
                                {{ $label }} ({{ $value }})
                            </option>
                        @endforeach
                    </select>

                    <label for="reminder_time_local">Reminder time</label>
                    <input
                        id="reminder_time_local"
                        name="reminder_time_local"
                        type="time"
                        value="{{ old('reminder_time_local', substr((string) $user->reminder_time_local, 0, 5)) }}"
                        required
                    >
                    <button type="submit">Save and confirm reminders</button>
                </form>

                <div class="links">
                    <a href="{{ route('onboarding.unsubscribe', ['user' => $user] + request()->query()) }}">Unsubscribe instead</a>
                </div>
            </section>
        </main>
    </body>
</html>
