# CashNest — UI/UX Design System

> **Companion to (FINAL, unchanged):** `ARCHITECTURE.md` (SAD v1.1) · `DATABASE_DESIGN.md` (v1.0) · `API_SPECIFICATION.md` (v1.0)
> **Role:** Lead Product Designer & Senior Flutter UI/UX Architect
> **Status:** Design System — v1.0
> **Goal:** A premium, Play-Store-quality Rewards & Earning experience built on **Material 3**, fully themeable at runtime (backed by the `themes` table and `/v1/theme`), with first-class dark mode and accessibility.
> **Constraints:** No Flutter code. No images/asset binaries. This is a design specification: tokens, rules, and layout blueprints (ASCII wireframes) that a designer and Flutter engineer can build from directly.

> **Runtime theming note:** All color, radius, and typography values here are the **default theme**. The app fetches the live theme from `/v1/theme` (DB `themes`: `primary_color`, `secondary_color`, `accent_color`, `background_color`, `logo_url`, `font_family`, `default_mode`, `extra`). Tokens below map 1:1 to those fields so the design system and backend stay in lockstep. Values in `extra` JSON extend this token set without a release.

---

## Table of Contents

1. Brand Identity · 2. Color Palette · 3. Typography · 4. Icon System · 5. Spacing System · 6. Border Radius · 7. Shadow System · 8. Buttons · 9. Cards · 10. Input Fields · 11. Bottom Navigation · 12. App Bar · 13. Home Screen · 14. Wallet · 15. Daily Check-in · 16. Scratch Card · 17. Spin Wheel · 18. Offerwall · 19. Referral · 20. Withdraw · 21. Notifications · 22. Profile · 23. Settings · 24. Support · 25. Loading States · 26. Empty States · 27. Error States · 28. Success States · 29. Animation Guidelines · 30. Responsive Design · 31. Accessibility · 32. Dark Mode · 33. Illustration Style · 34. Lottie Usage · 35. Premium UI Guidelines · 36. Material 3 Guidelines · 37. Admin Panel UI · 38. Dashboard Layout · 39. Design Tokens · 40. Future UI Expansion

---

## 1. Brand Identity

### 1.1 Logo Concept
- **Wordmark + symbol.** The mark is a stylized **nest holding a coin** — the "nest" formed by two curved strokes cupping a circular coin with a subtle "₹/$"-agnostic spark. It reads as *safety + savings + reward*.
- **Construction:** built on an 8pt grid; symbol fits a 1:1 safe box; wordmark "CashNest" set in the brand display font with a slightly tightened tracking and a raised dot/spark over the coin.
- **Variants:** full lockup (symbol + wordmark), symbol-only (app icon, avatars), monochrome (single-color for watermarks/receipts), and inverse (for dark surfaces). The `logo_url` in `themes` supplies the runtime logo; the app ships a bundled fallback.
- **Clear space:** minimum padding equal to the coin's radius on all sides. **Minimum size:** 24dp symbol in-app, 48dp app icon.
- **Don'ts:** no gradients on the symbol at small sizes, no rotation, no drop shadow on the mark, no recoloring outside brand palette.

### 1.2 Brand Personality
- **Trustworthy** (money is involved — clarity over cleverness), **Rewarding** (celebratory micro-moments), **Energetic** (fast, playful earning loops), **Effortless** (low cognitive load), **Premium** (crafted spacing, motion, and depth).
- **Voice & tone:** confident, friendly, concise. Positive reinforcement ("You earned ₹5!"), never dark-pattern pressure. Numbers are the hero; copy supports them.

### 1.3 Design Language
- **Foundation:** Google **Material 3 (Material You)** — dynamic color capable, expressive, accessible.
- **CashNest layer on top of M3:** *"Calm canvas, vivid rewards."* Neutral, airy backgrounds let reward moments (coins, scratch reveals, spins, confetti) pop with saturated accent color and motion.
- **Principles:**
  1. **Balance-first** — the wallet balance is always one glance away.
  2. **One primary action per screen** — a single, obvious next step.
  3. **Reward with motion** — every earn is acknowledged with tasteful animation.
  4. **Progressive disclosure** — advanced detail (ledger, KYC) is one tap deeper.
  5. **Consistency** — the same card, button, and spacing language everywhere.

---

## 2. Color Palette

> Semantic tokens (not raw hex in UI). Light values are defaults; §32 lists dark equivalents. Maps to `themes` columns where noted.

### 2.1 Brand / Primary
| Token | Hex (Light) | Maps to | Usage |
|-------|-------------|---------|-------|
| `color.primary` | `#1E88E5` | `themes.primary_color` | Primary buttons, active states, links, key accents. |
| `color.onPrimary` | `#FFFFFF` | — | Text/icons on primary. |
| `color.primaryContainer` | `#D3E6FB` | — | Tonal chips, selected nav, subtle emphasis. |
| `color.onPrimaryContainer` | `#0A3D6B` | — | Text on primary container. |

### 2.2 Secondary
| Token | Hex (Light) | Maps to | Usage |
|-------|-------------|---------|-------|
| `color.secondary` | `#5E35B1` | `themes.secondary_color` | Secondary emphasis, premium badges, referral. |
| `color.onSecondary` | `#FFFFFF` | — | On secondary. |
| `color.secondaryContainer` | `#E7DEF8` | — | Secondary tonal surfaces. |

### 2.3 Accent / Reward (the "money" color)
| Token | Hex (Light) | Maps to | Usage |
|-------|-------------|---------|-------|
| `color.accent` | `#FFB300` | `themes.accent_color` | Coins, rewards, spin/scratch highlights, celebratory. |
| `color.onAccent` | `#3A2A00` | — | Text on accent. |
| `color.coin` | `#FFC107` | `extra.coin` | Coin iconography/gradients. |

### 2.4 Semantic States
| Token | Hex (Light) | Usage |
|-------|-------------|-------|
| `color.success` | `#2E7D32` | Success, credited, approved, "paid". |
| `color.onSuccess` | `#FFFFFF` | On success. |
| `color.successContainer` | `#C8E6C9` | Success banners/toasts. |
| `color.warning` | `#F57C00` | Pending, caution, expiring soon. |
| `color.onWarning` | `#3A2400` | On warning. |
| `color.warningContainer` | `#FFE0B2` | Warning banners. |
| `color.error` | `#D32F2F` | Errors, rejected, insufficient balance, destructive. |
| `color.onError` | `#FFFFFF` | On error. |
| `color.errorContainer` | `#FFCDD2` | Error banners/field states. |
| `color.info` | `#0288D1` | Informational notices. |

### 2.5 Background & Surface
| Token | Hex (Light) | Maps to | Usage |
|-------|-------------|---------|-------|
| `color.background` | `#F6F7FB` | `themes.background_color` | App scaffold background. |
| `color.surface` | `#FFFFFF` | — | Cards, sheets, dialogs. |
| `color.surfaceVariant` | `#EEF1F6` | — | Subtle fills, disabled, divid"ish" fills. |
| `color.surfaceContainerLow` | `#F1F3F9` | — | Nested containers (M3 tonal). |
| `color.surfaceContainerHigh` | `#E9ECF4` | — | Elevated tonal containers. |
| `color.outline` | `#C4C9D4` | — | Borders, dividers, input outlines. |
| `color.outlineVariant` | `#E1E5EC` | — | Hairline dividers. |
| `color.scrim` | `#000000 @ 40%` | — | Modal/backdrop scrim. |

### 2.6 Text (content) colors
| Token | Hex (Light) | Usage |
|-------|-------------|-------|
| `color.textPrimary` | `#1A1C1E` | Headlines, primary content. |
| `color.textSecondary` | `#44474E` | Body, labels. |
| `color.textTertiary` | `#71757E` | Captions, hints, metadata. |
| `color.textDisabled` | `#A6AAB2` | Disabled text. |
| `color.textInverse` | `#FFFFFF` | Text on dark/colored surfaces. |
| `color.link` | `#1565C0` | Inline links. |

### 2.7 Gradients (premium accents — use sparingly)
| Token | Definition | Usage |
|-------|------------|-------|
| `gradient.wallet` | `#1E88E5 → #5E35B1` (135°) | Wallet balance hero card. |
| `gradient.reward` | `#FFB300 → #FF7043` (135°) | Scratch/spin reward surfaces. |
| `gradient.premium` | `#6A11CB → #2575FC` (135°) | Premium/VIP badges. |

### 2.8 Color Usage Rules
- **60/30/10:** ~60% neutral background/surface, ~30% primary, ~10% accent (reward). Accent is reserved for money/reward moments to keep it meaningful.
- Never convey status by color alone (pair with icon + label — see §31).
- All text/background pairs meet WCAG AA (§31). Gradients are decorative; never place small body text on a gradient.

---

## 3. Typography

- **Primary font family:** `Inter` (UI) with **Manrope** as the display alternative for hero numbers; `font_family` from `themes` overrides at runtime. System fallback: Roboto.
- **Numeric emphasis:** tabular figures for balances and amounts (aligned digits, no jitter on updates).

### 3.1 Type Scale (Material 3 aligned)
| Token | Size / Line | Weight | Usage |
|-------|-------------|--------|-------|
| `type.displayLarge` | 40 / 48 | 700 | Reward reveal amounts, celebratory. |
| `type.displayMedium` | 32 / 40 | 700 | Wallet balance hero. |
| `type.headlineLarge` | 28 / 36 | 700 | Screen hero titles. |
| `type.headlineMedium` | 24 / 32 | 600 | Section heroes. |
| `type.titleLarge` | 20 / 28 | 600 | App bar title, card titles. |
| `type.titleMedium` | 16 / 24 | 600 | List item titles, dialog titles. |
| `type.bodyLarge` | 16 / 24 | 400 | Primary body. |
| `type.bodyMedium` | 14 / 20 | 400 | Secondary body, descriptions. |
| `type.labelLarge` | 14 / 20 | 600 | Buttons, tabs. |
| `type.labelMedium` | 12 / 16 | 500 | Chips, captions. |
| `type.labelSmall` | 11 / 16 | 500 | Badges, overlines, metadata. |
| `type.mono` | 14 / 20 | 500 | Transaction IDs, codes (monospace). |

### 3.2 Rules
- Max **2 font families** in the app. Weights limited to 400/500/600/700.
- Line length target 60–75 chars for paragraphs. Never justify text.
- Currency/coins always use tabular figures + the coin glyph or currency symbol; positive = success color with `+`, negative = error color with `−`.
- Respect OS font-scaling up to 200% (see §31).

---

## 4. Icon System

- **Library:** **Material Symbols (Rounded)** as the base set for consistency with M3; brand/reward icons (coin, nest, spin, scratch, gift) are custom in the same rounded, 2dp-stroke, 24-grid style.
- **Style:** rounded terminals, 2dp optical stroke, filled variant for active/selected, outlined for inactive.
- **Sizes:** `16 / 20 / 24 / 32 / 40 / 48` dp. Default touch icon 24dp inside a 48dp target.
- **States:** inactive = `outline` weight + `textSecondary`; active = `filled` + `primary`.
- **Coin icon:** a distinct branded coin glyph used everywhere a coin balance appears — never substitute a generic currency symbol for coins.
- **Rules:** one metaphor per concept (e.g., only ever one "wallet" icon); pair icons with labels in navigation and key actions; keep custom icons on the same grid/stroke as Material Symbols so they don't look foreign.

---

## 5. Spacing System

- **Base unit:** `4dp`. All spacing is a multiple of 4 (8pt-ish grid with 4dp granularity).

| Token | Value | Usage |
|-------|-------|-------|
| `space.0` | 0 | Reset. |
| `space.1` | 4 | Icon-to-label, tight chips. |
| `space.2` | 8 | Between related elements. |
| `space.3` | 12 | Compact card padding. |
| `space.4` | 16 | **Default** screen/card padding, list gaps. |
| `space.5` | 20 | Comfortable grouping. |
| `space.6` | 24 | Section spacing. |
| `space.8` | 32 | Between major sections. |
| `space.10` | 40 | Hero spacing. |
| `space.12` | 48 | Large empty-state spacing. |

- **Screen gutters:** 16dp default (mobile). **Card internal padding:** 16dp. **List item vertical padding:** 12–16dp. **Section gap:** 24dp.

---

## 6. Border Radius System

| Token | Value | Usage |
|-------|-------|-------|
| `radius.xs` | 6 | Chips, small tags. |
| `radius.sm` | 10 | Inputs, small buttons. |
| `radius.md` | 14 | Buttons, list tiles. |
| `radius.lg` | 20 | **Cards (default)**, sheets top. |
| `radius.xl` | 28 | Hero cards, dialogs. |
| `radius.pill` | 999 | Pills, FAB, avatars, segmented toggles. |
| `radius.full` | circle | Coin, avatar, icon buttons. |

- Bottom sheets: top corners `radius.xl` (28), bottom square. Consistent radius per component type across the app.

---

## 7. Shadow / Elevation System

> M3 favors **tonal elevation** (surface tint) in light and **shadow-light + tint** blends. Define both a tonal level and a soft shadow.

| Token | Elevation | Shadow (light) | Usage |
|-------|-----------|----------------|-------|
| `elevation.0` | 0 | none | Flat backgrounds. |
| `elevation.1` | 1 | y2 blur6 `#000 @6%` | Cards at rest. |
| `elevation.2` | 3 | y4 blur10 `#000 @8%` | Raised cards, app bar on scroll. |
| `elevation.3` | 6 | y6 blur16 `#000 @10%` | FAB, menus. |
| `elevation.4` | 8 | y8 blur24 `#000 @12%` | Dialogs, bottom sheets. |
| `elevation.5` | 12 | y12 blur32 `#000 @14%` | Reward/spin overlays. |

- **Dark mode:** shadows are subtle; depth is conveyed primarily by tonal surface steps (`surfaceContainerLow/High`) plus a faint `#000 @ 30–40%` shadow.
- Avoid stacking heavy shadows; one elevation step between adjacent layers.

---

## 8. Button Design

### 8.1 Types (M3)
| Variant | Use | Style |
|---------|-----|-------|
| **Filled (Primary)** | Main action per screen ("Withdraw", "Claim") | `primary` bg, `onPrimary` text, `radius.md`, height 52dp. |
| **Filled Tonal** | Secondary important action | `primaryContainer` bg, `onPrimaryContainer` text. |
| **Outlined** | Alternative action | 1.5dp `outline` border, transparent bg, `primary` text. |
| **Text** | Low-emphasis ("Skip", "Cancel") | `primary` text only. |
| **Elevated** | On busy/media backgrounds | `surface` bg + `elevation.1`. |
| **FAB / Extended FAB** | Persistent key action (e.g., "Withdraw") | `radius.pill`, `elevation.3`. |
| **Icon button** | Compact actions | 48dp target, 24dp icon. |

### 8.2 Specs
- **Heights:** primary 52dp; compact 40dp; icon 48dp target.
- **Padding:** 24dp horizontal (text buttons 12dp). Icon+label gap 8dp.
- **Label:** `type.labelLarge`, sentence case, action verb first.
- **States:** default / hover (web) / **pressed** (state-layer 12% overlay) / focus (2dp focus ring) / **loading** (inline spinner replaces label, button width locked) / **disabled** (`surfaceVariant` bg, `textDisabled`, no shadow).
- **One filled primary per screen.** Destructive actions use `error` filled and require confirmation (§27).
- **Full-width** primary buttons on forms and bottom action bars; auto-width in compact contexts.

---

## 9. Card Design

- **Default card:** `surface` bg, `radius.lg` (20), `elevation.1`, 16dp padding, 16dp gap between cards.
- **Card anatomy:** optional leading icon/illustration → title (`titleMedium`) → supporting text (`bodyMedium`) → optional trailing value/chip → optional action row.
- **Variants:**
  - **Hero card** (wallet balance): gradient bg (`gradient.wallet`), `radius.xl`, `elevation.2`, white text, coin glyph, subtle animated shimmer on balance change.
  - **Action card** (spin/scratch/checkin): square-ish, illustration top, bold reward hint, single CTA.
  - **List card / tile:** compact, `radius.md`, used in transaction/offer lists.
  - **Stat card** (admin/dashboard): metric-forward, big number + delta chip.
- **Rules:** cards never nest more than one level; consistent corner radius per type; tappable cards show a state layer + slight scale (0.98) on press.

---

## 10. Input Fields

- **Style:** M3 **Outlined** text fields by default (filled variant only inside dense sheets).
- **Anatomy:** floating label → input → helper/counter → leading/trailing icon. `radius.sm`, height 56dp, 16dp inner padding.
- **States:** default (`outline`), focused (2dp `primary` border + label color primary), error (`error` border + helper text + error icon), disabled (`surfaceVariant`), success (subtle `success` check for validated fields like referral code).
- **Field types:** text, email, password (with reveal toggle), numeric (coins/amount — numeric keyboard, tabular figures), OTP (6 segmented boxes), dropdown/select, search (pill, leading search icon, clear button).
- **Validation UX:** validate on blur + on submit; inline error under field mirroring API `errors[].message` (from `API_SPECIFICATION.md` §1.6). Never block typing; show constraints proactively (e.g., "8+ chars, 1 number").
- **Amount input (withdraw):** shows coin→cash conversion live, min/max hints, and "Max" quick-fill chip.

---

## 11. Bottom Navigation

- **Pattern:** M3 **Navigation Bar**, 5 destinations max, 80dp height, `surface` + `elevation.2`, active pill indicator (`primaryContainer`).
- **Destinations:**
  1. **Home** (dashboard/earn hub)
  2. **Earn** (offerwall/tasks/CPA)
  3. **Wallet** (balance + transactions)
  4. **Rewards** (spin/scratch/check-in/leaderboard) *or* **Refer**
  5. **Profile** (account, settings, support)
- **Item:** filled icon + label when active (`primary`), outlined + `textSecondary` when inactive. Labels always shown (accessibility).
- **Badges:** unread count on Wallet/Profile (notifications), pulse dot on Rewards when a spin/scratch/check-in is available.
- **Behavior:** preserve per-tab scroll/navigation stack; center primary action (Withdraw) may be a docked extended FAB on Wallet.

---

## 12. App Bar

- **Types:**
  - **Home app bar:** large/expressive — greeting ("Hi, Asha 👋") + avatar + notification bell (with badge) + compact balance chip on the right. Collapses to a small bar on scroll (M3 large top app bar → small).
  - **Standard app bar:** back button + centered/left title (`titleLarge`) + up to 2 actions.
  - **Contextual app bar:** selection mode (admin/lists) with count + bulk actions.
- **Specs:** 64dp (small), up to 152dp (large expanded). `surface` bg; gains `elevation.2` + tint on scroll.
- **Balance chip:** persistent quick view of coin balance that taps through to Wallet.
- **Rules:** one screen title; no more than 2 trailing actions (overflow menu beyond that).

---

## 13. Home Screen Layout

> Server-driven via `/v1/home/layout` (`home_sections`). The design supports reorderable, toggleable sections; below is the default composition.

```
┌───────────────────────────────────────────┐
│  App Bar (large): Hi, Asha 👋   🔔•  [👛 4,200]│  ← greeting, notif badge, balance chip
├───────────────────────────────────────────┤
│  WALLET HERO CARD (gradient)                │
│   Coins 4,200   ≈ ₹4.20                      │
│   [ Withdraw ]   [ + Earn ]                  │
├───────────────────────────────────────────┤
│  DAILY STRIP: [Check-in ✓] [Spin •] [Scratch•]│  ← quick_actions section, availability dots
├───────────────────────────────────────────┤
│  BANNER CAROUSEL (auto, dots)               │  ← banner_carousel (banners API)
├───────────────────────────────────────────┤
│  EARN MORE  ▸                               │
│   ┌────────┐ ┌────────┐ ┌────────┐          │
│   │Offerwall│ │ Tasks  │ │  CPA   │          │  ← horizontal cards
│   └────────┘ └────────┘ └────────┘          │
├───────────────────────────────────────────┤
│  TOP OFFERS (list, 3–5)              See all ▸│  ← offers section
├───────────────────────────────────────────┤
│  LEADERBOARD PREVIEW (your rank + top 3)     │
├───────────────────────────────────────────┤
│  REFER & EARN banner (secondary gradient)    │
└───────────────────────────────────────────┘
   Bottom Navigation (Home active)
```

- **Priorities:** balance + primary earn actions above the fold; availability dots draw users into daily loops.
- **Personalization:** sections/audience come from `home_sections`; unknown section types are ignored (forward-compatible per API §2.80).
- **Pull-to-refresh** refetches layout, banners, wallet, and config.

---

## 14. Wallet Screen

```
┌───────────────────────────────────────────┐
│  App Bar: Wallet                             │
├───────────────────────────────────────────┤
│  BALANCE HERO (gradient)                     │
│   4,200 coins   ≈ ₹4.20                       │
│   Reserved: 0    Lifetime earned: 12,000      │
│   [ Withdraw ]  (Extended FAB / primary)      │
├───────────────────────────────────────────┤
│  FILTER ROW: [All][Earned][Spent][Withdraw]  │  ← chips → filter[type]/direction
├───────────────────────────────────────────┤
│  TRANSACTIONS (grouped by day)               │
│   Today                                       │
│    ● +100  Offerwall — AdGate      18:00      │  green +, coin glyph
│    ● −5,000 Withdrawal hold        12:10      │  amber pending
│   Yesterday                                   │
│    ● +30  Daily check-in           09:00      │
│   … infinite scroll (cursor pagination)       │
└───────────────────────────────────────────┘
```

- **Transaction row:** direction dot/icon (credit=success, debit=error, hold=warning) + type label + source + time + signed amount (tabular). Tap → detail sheet (uuid, metadata, related reversal).
- **Empty state** if no transactions (§26). Balance change animates (count-up) when returning after an earn.
- **Detail bottom sheet:** amount hero, type, reference, timestamp, status, and a "Report a problem" link → Support.

---

## 15. Daily Check-in UI

```
┌───────────────────────────────────────────┐
│  Daily Check-in            Streak: 🔥 3 days │
├───────────────────────────────────────────┤
│  7-DAY LADDER (horizontal)                   │
│  [D1✓][D2✓][D3●][D4][D5][D6][D7★]            │  ✓ claimed, ● today, ★ milestone
│   +10  +15  +30  +40  +50  +75  +150          │
├───────────────────────────────────────────┤
│         [  Claim +30 coins  ]  (primary)      │
│   "Come back tomorrow to keep your streak!"   │
└───────────────────────────────────────────┘
```

- **Interaction:** tapping Claim triggers a coin-burst Lottie + count-up on balance; claimed day flips with a check animation; streak flame pulses.
- **States:** claimable (bright CTA), already claimed today (CTA → "Claimed ✓", disabled, countdown to next), streak-broken (gentle reset message, no blame).
- **Milestone days** (★) use accent gradient and a bigger celebration.

---

## 16. Scratch Card UI

```
┌───────────────────────────────────────────┐
│  Scratch Cards                    Available:2│
├───────────────────────────────────────────┤
│  ┌───────────────┐   ┌───────────────┐      │
│  │  ✨ SCRATCH   │   │   CLAIMED     │      │
│  │  (foil cover) │   │   +50 coins   │      │
│  └───────────────┘   └───────────────┘      │
│   expires in 2d                              │
└───────────────────────────────────────────┘
```

- **Reveal interaction:** finger-drag scratch gesture over a foil texture; at ~60% scratched, auto-reveal with a shimmer + confetti. Reward comes from server (`/scratch/{id}/reveal`) — the UI plays the reveal only after the server responds (no client-decided value).
- **Two-step flow:** reveal → **Claim** button credits coins (matches API reveal/claim split). Reward number uses `displayLarge` + accent gradient.
- **States:** issued (foil), revealed-unclaimed (reward visible + Claim), claimed (dimmed + amount), expiring (warning chip), expired (grayscale, "Expired").
- **Haptics:** light tick during scratch, success haptic on reveal.

---

## 17. Spin Wheel UI

```
┌───────────────────────────────────────────┐
│  Spin & Win              Spins left: 2 / 3   │
├───────────────────────────────────────────┤
│                ▲ pointer                     │
│            ╭───────────╮                     │
│          ╱  50 │ 10 │ 5  ╲                    │
│         │ 100 │  ⭐  │ 20 │   ← segments      │
│          ╲  0  │ 200│ 30 ╱                    │
│            ╰───────────╯                     │
│              [  SPIN  ]  (accent gradient)    │
│   "Out of spins? Watch an ad for +1"          │
└───────────────────────────────────────────┘
```

- **Physics:** ease-out deceleration (2.5–3.5s), lands **exactly** on the server-returned segment (`POST /v1/spin`), never client-random. Slight over-rotate + settle.
- **Result:** modal with reward, coin-burst Lottie, count-up on balance, "Spin again" or "Come back tomorrow".
- **States:** spins available (glowing SPIN), spinning (button → disabled, wheel animating), no spins (CTA becomes "Watch ad for +1" → rewarded-ad flow → `/ads/rewarded/verify`).
- **Segment colors** from `spin_wheel_segments.color_hex`; jackpot (★) uses accent + sparkle.

---

## 18. Offerwall UI

```
┌───────────────────────────────────────────┐
│  Earn — Offers        [Offerwall|Tasks|CPA]  │  ← top tabs
├───────────────────────────────────────────┤
│  Provider chips: [All][AdGate][OfferToro]…   │
│  Sort: [Reward ▾]    Filter ⚙                │
├───────────────────────────────────────────┤
│  OFFER CARD                                  │
│   [icon] Install & reach level 5             │
│          +2,000 coins   • Easy • 5 min       │
│                              [  Start  ] ▸    │
│  OFFER CARD …                                 │
│   … infinite scroll                          │
└───────────────────────────────────────────┘
```

- **Offer card:** provider/app icon, title, payout in accent coins, difficulty/time tags, Start CTA. Tap Start → `/offerwall/offers/{id}/click` → open in-app browser/redirect.
- **Attribution note:** subtle "Rewards credit after completion (may take a few minutes)" to set expectations for postback delay.
- **Empty/geo:** if no offers for the region, show empty state with alternatives (Tasks, Spin).
- **Trust cues:** provider logos, payout prominent, honest timing copy — no fake urgency.

---

## 19. Referral UI

```
┌───────────────────────────────────────────┐
│  Refer & Earn (secondary gradient hero)      │
│   Invite friends, earn 500 coins each        │
│   Your code:  ASHA12   [ Copy ] [ Share ]    │
├───────────────────────────────────────────┤
│  STATS: Invited 8 · Qualified 5 · Earned 2,500│
├───────────────────────────────────────────┤
│  HOW IT WORKS  1→2→3 (illustrated steps)      │
├───────────────────────────────────────────┤
│  YOUR REFERRALS (list)                        │
│   Ravi   • Qualified   +500                   │
│   Neha   • Pending     —                       │
└───────────────────────────────────────────┘
```

- **Share:** native share sheet with prefilled message + `referral_link`. Prominent Copy with success toast + haptic.
- **Status chips:** pending (warning), qualified (success), rewarded (accent).
- **Anti-abuse tone:** clarify qualification ("Friend must complete first earn") transparently.

---

## 20. Withdraw UI

```
Step 1 — Method            Step 2 — Amount           Step 3 — Confirm
┌───────────────┐          ┌───────────────┐         ┌───────────────┐
│ Choose method │          │ Enter amount  │         │ Review        │
│ ◉ UPI         │          │ [ 5,000 ] Max │         │ 5,000 coins    │
│ ○ PayPal      │   ▸      │ ≈ ₹5.00        │   ▸     │ ≈ ₹5.00        │
│ ○ Amazon GC   │          │ min 5,000      │         │ To: user@upi   │
│               │          │ fee ₹0.00      │         │ Fee ₹0.00      │
│ [ Continue ]  │          │ [ Continue ]   │         │ [ Withdraw ]   │
└───────────────┘          └───────────────┘         └───────────────┘
```

- **Guardrails:** live conversion, min/max hints, "Max" chip, disabled Continue until valid. If balance/KYC/fraud blocks → clear inline message mapped from API error codes (`INSUFFICIENT_BALANCE`, `MIN_WITHDRAW_NOT_MET`, `KYC_REQUIRED`, `FRAUD_HOLD`).
- **Confirm:** shows exact coins debited, cash net, destination, fee — no surprises. Requires explicit confirm (irreversible tone).
- **Success:** success screen (§28) with request id + "Track status" → Withdraw History.
- **Withdraw History:** list with status timeline (pending → approved → processing → paid / rejected) using a vertical stepper; each step timestamped from `withdraw_history`. Cancel available only while `pending`.

---

## 21. Notification UI

- **In-app inbox:** list grouped by date; unread rows have a filled dot + slightly stronger surface; read rows subdued. Row = icon (by `type`) + title + preview + time.
- **Types & color accents:** transactional (primary), engagement (accent), promotional (secondary), system (info).
- **Actions:** tap → deep link (`deep_link`); swipe → mark read; "Mark all read" in app bar; badge count from `/notifications/unread-count`.
- **Push (system tray):** channel-separated (Transactional vs Promotional per Android channels); rich image optional; tapping opens the mapped screen.
- **Empty state** when inbox is clear (§26). Respect user toggles from Settings.

---

## 22. Profile UI

```
┌───────────────────────────────────────────┐
│  [avatar]  Asha  · ASHA12                    │
│  asha@example.com     KYC: ✓ Verified         │
│  Coins 4,200 · Rank #42                        │
├───────────────────────────────────────────┤
│  ▸ Wallet & Transactions                      │
│  ▸ Refer & Earn                               │
│  ▸ Withdraw History                           │
│  ▸ KYC / Verification                          │
│  ▸ Settings                                    │
│  ▸ Support & Help                              │
│  ▸ Legal (Privacy, Terms, About)               │
│  ▸ Logout                                      │
└───────────────────────────────────────────┘
```

- Editable name/avatar (avatar upload per API §2.12). KYC status chip (color-coded). Grouped list rows with leading icons, chevrons, and dividers via `outlineVariant`.
- **Danger zone:** Delete account (error text button) with confirmation dialog + consequences (blocked if pending withdrawals).

---

## 23. Settings UI

- **Grouped sections:** Notifications (master + transactional + promotional toggles), Appearance (Theme mode: System/Light/Dark), Language (locale picker → `Accept-Language`), Security (change password, active sessions/logout), About (app version, force-update prompt if outdated), Legal (CMS links).
- **Controls:** M3 switches for toggles, radio/segmented for theme mode, list-select for language.
- **Version row:** shows current vs latest (`/v1/app/version`); "Update available" chip → store.
- Changes persist via `/v1/settings` and reflect immediately (optimistic with rollback on error).

---

## 24. Support UI

- **Help home:** search bar → FAQ (grouped by category from CMS) + "Contact us" CTA.
- **FAQ:** accordion list; expanding animates height; answers rendered from sanitized CMS HTML.
- **Tickets:** list (status chips) → thread view (chat-style bubbles: user right/primary tonal, staff left/surface), composer with attach; closed tickets are read-only with "Reopen".
- **Empty state** for no tickets (§26). Response-time expectation copy sets tone.

---

## 25. Loading States

- **Skeletons first, spinners rarely.** Use shimmer skeletons matching final layout (cards, list rows, balance) for initial loads.
- **Inline button loading:** label → spinner, width locked, disabled (§8).
- **Full-screen loader:** only for blocking auth/splash; branded (logo + subtle Lottie), never a bare spinner.
- **Pagination:** footer spinner + skeleton rows while fetching next cursor page.
- **Pull-to-refresh:** M3 refresh indicator in `primary`.
- **Optimistic UI:** for reads like mark-read and settings toggles; reconcile/rollback on API error.
- **Timing:** show skeleton after 150ms delay to avoid flicker on fast responses; minimum visible 400ms to avoid flash.

---

## 26. Empty States

- **Anatomy:** friendly illustration (§33) + short headline + one-line supportive text + a primary CTA that routes to the fix.
- **Examples:**
  - No transactions → "No earnings yet — spin, scratch, or try an offer" → [Start earning].
  - No offers in region → "No offers here right now" → [Do a task].
  - Empty inbox → "You're all caught up".
  - No referrals → "Invite your first friend" → [Share code].
- **Rules:** never a blank screen; empty ≠ error; keep copy positive and action-oriented.

---

## 27. Error States

- **Levels:**
  - **Inline field error** (forms) — under input, `error` color + icon, text from API `errors[].message`.
  - **Banner/snackbar** — transient recoverable errors ("Couldn't load offers") with **Retry**.
  - **Full-screen error** — for hard failures (no network, 500) — illustration + message + Retry.
  - **Blocking gates** — `MAINTENANCE_MODE` (full maintenance screen with message from `/app/maintenance`), `FORCE_UPDATE_REQUIRED` (non-dismissible update screen → store).
- **Mapping:** each standard API error code (API §1.7) maps to a specific message + recovery. Never expose raw codes/stack traces to users; show human copy, log `request_id` for support.
- **Destructive confirmations:** dialogs for withdraw/delete/logout with clear consequence and a distinct destructive button.
- **Tone:** blameless, specific, actionable ("Check your connection and retry").

---

## 28. Success States

- **Micro-success:** snackbar/toast with success color + check icon (copy, mark-read, saved settings) + optional haptic.
- **Reward success:** celebratory overlay — coin-burst/confetti Lottie, `displayLarge` amount, count-up on balance, single "Awesome" dismiss. Used for check-in, scratch, spin, task credit.
- **Transactional success:** dedicated screen for Withdraw request created — big check, request id, "Track status" CTA.
- **Rules:** celebrate proportionally (bigger reward → bigger moment), but keep dismissal one tap; never trap the user.

---

## 29. Animation Guidelines

- **Motion principles:** purposeful, quick, physical. Motion communicates state, hierarchy, and reward — never decoration for its own sake.
- **Durations:** micro 100–150ms; standard transitions 200–300ms; emphasized/celebration 400–600ms (M3 emphasized easing).
- **Easing:** M3 `emphasized` for entrances, `standard` for most, `emphasized-decelerate` for incoming, `accelerate` for exits.
- **Key motions:**
  - Balance **count-up** on any credit.
  - **Shared-element** transitions (offer card → detail, scratch card → reveal).
  - **State layers** on press; card scale-to-0.98 on tap.
  - **List item** stagger-in (subtle, ≤ 60ms offset).
  - **Reward bursts** (confetti/coins) for celebrations only.
- **Performance:** target 60fps (120 where available); avoid layout thrash; cap simultaneous heavy animations. Respect "reduce motion" (§31) by disabling non-essential/celebratory motion (keep functional transitions minimal/instant).

---

## 30. Responsive Design Rules

- **Breakpoints (M3 window size classes):**
  | Class | Width | Layout |
  |-------|-------|--------|
  | Compact | <600dp | Single column, bottom nav (phones — primary). |
  | Medium | 600–839dp | 2-column grids, **Navigation Rail**, wider cards (large phones/small tablets). |
  | Expanded | ≥840dp | Multi-column, rail + optional list-detail, max content width ~1040dp centered (tablets/foldables/web admin). |
- **Rules:** content max-width caps on large screens (avoid ultra-wide line lengths); grids reflow (offers/cards 2→3→4 columns); bottom nav → rail at Medium+; images/illustrations use responsive sizing (`max-width:100%`).
- **Orientation:** support landscape (esp. spin/scratch): center the interactive element, keep CTA reachable.
- **Safe areas:** respect notches/gesture insets; sticky bottom action bars sit above the gesture bar.

---

## 31. Accessibility Rules

- **Contrast:** WCAG **AA** minimum — 4.5:1 body text, 3:1 large text (≥18.66dp bold / 24dp) and UI/icon boundaries. All semantic pairs in §2 validated for both themes.
- **Touch targets:** minimum **48×48dp**; 8dp min spacing between targets.
- **Text scaling:** support OS font scale to **200%**; layouts must not clip or overlap — use flexible/wrapping layouts, not fixed heights for text.
- **Color independence:** status always pairs color with icon + text (pending/approved/rejected never color-only).
- **Screen readers:** every interactive element has a semantic label; images/icons have alt/semantics; announce dynamic changes (balance updated, error appeared) via live regions.
- **Focus:** visible focus ring (2dp) for keyboard/switch access (web admin especially); logical focus order.
- **Motion sensitivity:** honor OS "reduce motion" — disable confetti/parallax/auto-carousel, keep essential fades.
- **Forms:** labels always visible (not placeholder-only); errors announced and tied to their field.
- **Haptics:** meaningful but optional; never the only feedback channel.

---

## 32. Dark Mode Design

> Not an inversion — a purpose-built dark theme. `default_mode` from `themes` + user override in Settings (System/Light/Dark).

| Token | Dark value | Notes |
|-------|-----------|-------|
| `color.background` | `#0F1216` | True-ish dark, not pure black (OLED-friendly but comfortable). |
| `color.surface` | `#161A20` | Base card surface. |
| `color.surfaceContainerLow` | `#1B2027` | Nested. |
| `color.surfaceContainerHigh` | `#232A33` | Elevated. |
| `color.primary` | `#7FB4F0` | Lightened primary for contrast on dark. |
| `color.onPrimary` | `#0A2540` | — |
| `color.accent` | `#FFC94D` | Lightened accent (coins glow). |
| `color.success` | `#7BC67E` | — |
| `color.warning` | `#FFB74D` | — |
| `color.error` | `#F2837E` | — |
| `color.textPrimary` | `#E6E8EC` | — |
| `color.textSecondary` | `#B4B9C2` | — |
| `color.textTertiary` | `#868C97` | — |
| `color.outline` | `#3A414B` | — |

- **Rules:** convey elevation with **tonal surface steps** (each higher layer lighter), minimal shadow. Reduce large saturated fills; gradients get slightly muted. Desaturate illustrations for dark. Maintain AA contrast in both themes. Test every screen in both modes before release.

---

## 33. Illustration Style

- **Style:** modern flat with soft depth — rounded shapes, 2-tone brand-tinted palettes, gentle gradients, subtle grain optional. Friendly characters + coin/nest motifs.
- **Usage:** empty states, onboarding, success screens, referral steps, error screens.
- **Consistency:** shared palette (brand + accent), consistent stroke/corner language matching the icon system; characters diverse and inclusive.
- **Theming:** provide light + dark variants (desaturated for dark); ship as vector (SVG) for crispness and small size.
- **Don'ts:** no stocky 3D renders mixed with flat art; no photo backgrounds behind text; keep file sizes small for shared-hosting-served assets/CDN.

---

## 34. Lottie Animation Usage

- **Where:** reward celebrations (coin burst, confetti), spin result, scratch reveal shimmer, check-in flame, success checkmarks, branded splash, and empty-state gentle loops.
- **Rules:**
  - Keep files small (<100KB where possible); optimize/limit layers; prefer looping only for idle states, one-shot for celebrations.
  - **Never gate a reward on animation completion** — the server result is truth; allow skip/tap-to-dismiss.
  - Respect reduce-motion (§31): swap Lottie for a static success frame.
  - Cache animations locally; don't fetch heavy Lottie on the critical path.
  - Consistent timing with §29 (celebration 400–600ms core, confetti tail up to ~1.2s but dismissible).

---

## 35. Premium UI Guidelines

What makes CashNest feel *premium*, not a cheap earner clone:

1. **Generous whitespace & rhythm** — consistent 4dp system, never cramped.
2. **Crafted motion** — count-ups, shared elements, tasteful celebrations (§29).
3. **Depth done right** — tonal M3 elevation, soft single-layer shadows, no harsh borders.
4. **Typography discipline** — tabular numbers, clear hierarchy, 2 fonts max.
5. **Consistent components** — one card, one button system, one radius language.
6. **Honest, confident copy** — no dark patterns, no fake countdowns, transparent reward timing.
7. **Delightful details** — haptics on key wins, coin glyph, gradient hero balance, micro-interactions on tap.
8. **Fast & smooth** — skeletons over spinners, 60fps, optimistic reads.
9. **Polished edge cases** — every empty/error/loading/success state designed (§25–28).
10. **Brand cohesion** — logo, color, illustration, and motion all speak one voice.

---

## 36. Google Material 3 Guidelines

- **Baseline:** the app is Material 3. Use M3 components (Navigation Bar/Rail, Top App Bar variants, Filled/Tonal/Outlined buttons, Cards, Text Fields, Chips, Bottom Sheets, Dialogs, Snackbars, Switches, Segmented buttons, FAB).
- **Color system:** M3 roles (primary/secondary/tertiary + containers, surface tones, outline). CashNest maps its brand tokens (§2) onto these roles; **dynamic color** (Material You from wallpaper) is optional and OFF by default to preserve brand — can be exposed as a setting later.
- **Elevation:** tonal + shadow blend per §7.
- **Shape:** M3 shape scale mapped to §6 radii.
- **State layers:** standard M3 opacities (hover 8%, focus 10%, pressed 12%).
- **Motion:** M3 easing/duration tokens (§29).
- **Density:** comfortable on mobile; compact density allowed in admin tables.
- **Adherence rule:** deviate from M3 only for branded reward moments (spin/scratch/celebrations) — everything structural stays M3 for familiarity and accessibility.

---

## 37. Admin Panel UI Design

> Web, server-rendered (per SAD). Same brand tokens, tuned for **data density** and desktop.

- **Shell:** left **sidebar navigation** (collapsible) + top bar (search, admin avatar, environment badge, notifications). Content area max-width fluid with 24dp gutters.
- **Navigation groups:** Dashboard · Users · Wallet · Rewards · Offerwall · Ads · Payments · Withdrawals · Referral · Notifications · CMS/Content · Config · Reports · Settings · Backups (RBAC-filtered — hide what the role can't access, per `admin_permissions`).
- **Components:** data **tables** (sortable, filterable, paginated, bulk-select, sticky header), stat cards, filter bar, detail drawers/side panels, form pages, modals for confirmations.
- **Density:** compact rows (40–48dp), tabular figures for money, monospace for IDs.
- **Status system:** consistent chips (pending/approved/paid/rejected/held) reusing semantic colors.
- **Destructive & sensitive ops:** restore/credential/maintenance actions require re-auth + dual confirm modals (matches SAD §6.3); secrets shown masked/write-only.
- **Audit affordance:** "History" panel on entities (who changed what) surfacing `admin_audit_logs`.
- **Responsive:** sidebar → icon rail on narrow; tables scroll horizontally within their own container (never break page layout).
- **Accessibility:** full keyboard nav, visible focus, AA contrast — same standards as the app.

---

## 38. Dashboard Layout (Admin)

```
┌── Sidebar ──┬───────────────────── Top Bar (search · env · 🔔 · admin) ─────────┐
│ Dashboard ● │  DASHBOARD                                   Date range: [Last 7d ▾] │
│ Users       ├───────────────────────────────────────────────────────────────────┤
│ Wallet      │  STAT CARDS (KPIs)                                                   │
│ Rewards     │  ┌ DAU/MAU ┐ ┌ New users ┐ ┌ Coins issued ┐ ┌ Coins redeemed ┐      │
│ Offerwall   │  │ 12,340  │ │  +1,204   │ │  4.2M ▲6%     │ │  3.1M ▲4%       │      │
│ Ads         │  └─────────┘ └───────────┘ └──────────────┘ └────────────────┘      │
│ Payments    │  ┌ Pending withdrawals ┐ ┌ Conversion revenue ┐ ┌ Fraud flags ┐     │
│ Withdrawals │  │  38  ⚠               │ │  ₹1.8L ▲          │ │  5 open ⚠     │     │
│ Referral    ├───────────────────────────────────────────────────────────────────┤
│ Notifs      │  CHARTS                                                              │
│ Content     │  [ Earnings vs Redemptions (line) ]   [ User growth (area) ]         │
│ Config      │  [ Offer performance (bar) ]          [ Withdrawals by status (donut)]│
│ Reports     ├───────────────────────────────────────────────────────────────────┤
│ Settings    │  QUEUES  →  Pending Withdrawals table (approve/reject)               │
│ Backups     │            Recent Fraud Flags · Open Support Tickets                 │
└─────────────┴───────────────────────────────────────────────────────────────────┘
```

- **KPI cards:** big number + delta chip (green up / red down) + sparkline; each links to its module.
- **Charts:** follow the `dataviz` discipline — accessible categorical colors, clear axes, tooltips; financial charts (issued vs redeemed) are the hero.
- **Actionable queues:** pending withdrawals and fraud flags surfaced directly for one-click triage (RBAC-gated).
- **Date range** filter applies across cards/charts; empty/loading/error states designed per §25–27.

---

## 39. Design Tokens

> Single source of truth. Delivered as a platform-agnostic token set (e.g., JSON) consumed by the app theme and mapped to `themes`/`extra` for runtime overrides. Naming: `category.token.state`.

### 39.1 Token Categories
| Category | Prefix | Source of truth |
|----------|--------|-----------------|
| Color | `color.*` | §2 (+ `themes` runtime override) |
| Gradient | `gradient.*` | §2.7 |
| Typography | `type.*` | §3 (`font_family` runtime) |
| Spacing | `space.*` | §5 |
| Radius | `radius.*` | §6 |
| Elevation/Shadow | `elevation.*` | §7 |
| Motion | `motion.duration.*`, `motion.easing.*` | §29 |
| Iconography | `icon.size.*` | §4 |
| Opacity/State | `state.hover/focus/pressed` | §36 |
| Breakpoints | `breakpoint.*` | §30 |

### 39.2 Example token map (illustrative, not code)
```
color.primary            = #1E88E5   (dark: #7FB4F0)   ← themes.primary_color
color.accent             = #FFB300   (dark: #FFC94D)   ← themes.accent_color
color.background         = #F6F7FB   (dark: #0F1216)   ← themes.background_color
radius.lg                = 20
space.4                  = 16
elevation.1              = {dy:2, blur:6, color:#000 @6%}
type.displayMedium       = {size:32, line:40, weight:700}
motion.duration.standard = 250ms
motion.easing.emphasized = cubic-bezier(0.2, 0.0, 0, 1.0)
breakpoint.compactMax    = 599
```

### 39.3 Token Rules
- UI references **semantic tokens only** — never raw hex/px in screens.
- Runtime theme (`/v1/theme`) overrides `color.primary/secondary/accent/background`, `logo`, and `font_family`; `themes.extra` (JSON) can inject additional tokens (e.g., seasonal accents) without a release.
- Light/dark values are paired per token; components read the active mode.
- Any new component must express itself entirely in existing tokens; new tokens are added centrally, reviewed, then used.

---

## 40. Future UI Expansion Strategy

- **Theming at scale:** seasonal/event themes and per-audience themes purely via `themes` + `remote_configs` (no release). A/B test themes through remote config %-rollout.
- **Server-driven UI growth:** extend `home_sections` with new block types; clients ignore unknown types gracefully today, so new sections ship server-side first, then get bespoke renderers.
- **iOS parity:** the token system + M3-aligned components port to a Cupertino-adaptive layer; tokens stay shared, platform components adapt.
- **New reward mechanics** (e.g., missions, streak leagues, mystery boxes): reuse the reward-moment pattern (server-authoritative outcome + celebration overlay + count-up).
- **Localization & RTL:** design already label-first and flexible; add RTL mirroring and locale-specific number/currency formatting; illustrations avoid embedded text.
- **Accessibility maturation:** progress toward AAA on key financial screens; add high-contrast theme variant via tokens.
- **Design ops:** maintain the token set as the contract between design and engineering; component library (Figma) mirrors this doc; every new screen must define its loading/empty/error/success states before build.
- **Web/PWA & tablet:** the responsive rules (§30) and admin patterns (§37) provide a path to a richer large-screen user experience without redesign.

---

*End of UI/UX Design System — CashNest v1.0. Aligned to Material 3, runtime-themeable via the `themes` table and `/v1/theme`, and consistent with the FINAL architecture, database, and API documents. No Flutter code or images generated.*
