# weightsy

Low-friction health metric tracker using text replies and minimal app dependency.

## MVP Goal

Allow users to submit daily weight, blood pressure, and body fat with the least friction possible.

## Why This Is Feasible

- SMS and push reply workflows are straightforward.
- Parsing structured short inputs is simple and robust.
- Minimal profile data model is practical.

## MVP Scope

- Daily reminder via SMS or push.
- Reply parsing:
  - `180` -> weight
  - `120/80` -> blood pressure
  - `18%` -> body fat
- Trend charts and history.
- Optional CSV export.

## Privacy Posture (MVP)

- Use phone as primary identifier.
- Avoid collecting DOB/address/full legal name.
- Capture only necessary consent and timezone.

