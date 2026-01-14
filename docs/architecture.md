# Underworld Savages — Codex Breakdown

## Global Architecture Context
This is a server-authoritative, text-based MMORPG mafia game. All game logic is handled on the backend. Clients are thin UI layers (web + mobile). Gameplay is time-based and request/response driven, not realtime simulation. Redis is used for cooldowns, locks, and shared state. PostgreSQL is the source of truth.

## 1. Auth & Player Core
**System:** Player Account

**Responsibilities**
- Authentication
- Session management
- Platform-agnostic login (web, iOS, Android)

**Core Data**
- player_id
- username
- email
- password_hash
- created_at
- last_login_at
- vip_tier
- gold_balance

**Actions**
- Register
- Login
- Logout
- Refresh session
- Update profile

## 2. Player Profile System
**System:** Player Profile

**Responsibilities**
- Display public player identity
- Track progression and stats

**Core Data**
- level
- rank
- experience_percent
- days_played
- current_city
- family_id
- marriage_id
- kills
- deaths
- crimes_committed
- oc_participation
- bounty_collected

**Actions**
- View profile
- Update bio
- Leave profile comment
- Send interaction (attack, message, bounty, proposal)

## 3. Crime System (Core Loop)
**System:** Crimes

**Responsibilities**
- Handle crime attempts
- Calculate success/failure
- Modify player progression
- Apply cooldowns
- Affect city police heat

**Core Data**
- crime_id
- crime_tier
- base_reward
- success_percentage
- cooldown_seconds
- heat_generated

**Actions**
- Attempt crime
- Resolve outcome
- Apply rewards/penalties
- Update success percentage
- Start cooldown (Redis)

**Rules**
- Each crime has independent success %
- % increases on success, may decrease on failure
- Police heat modifies final chance
- Redis lock prevents double submit

## 4. Grand Theft Auto (GTA)
**System:** Vehicle Theft

**Responsibilities**
- Steal vehicles
- Manage garage inventory
- Interface with OC driver role

**Core Data**
- vehicle_id
- vehicle_tier
- origin_city
- current_city
- damage_percent
- value
- armored_flag

**Actions**
- Steal vehicle
- Sell vehicle
- Crush vehicle
- Repair vehicle
- Armor vehicle
- Ship vehicle to another city
- Assign vehicle to OC

**Rules**
- Theft success based on GTA skill %
- Vehicles can be damaged or flagged
- Police may discover idle vehicles
- Better cars boost OC success

## 5. Organized Crime (OC) System
**System:** Organized Crimes

**Responsibilities**
- Coordinate multi-player crimes
- Validate role requirements
- Calculate combined success chance
- Resolve outcomes for all participants

**Core Data**
- oc_id
- oc_type
- required_roles
- participant_ids
- role_assignments
- success_threshold
- city_heat_modifier

**Actions**
- Create OC
- Join OC
- Assign role
- Start OC
- Resolve OC (async job)

**Rules**
- Each role contributes % to total
- Weak roles drag team down
- City heat reduces final chance
- Outcome applied to all players
- Redis lock during execution

## 6. City & Police Heat System
**System:** City Heat

**Responsibilities**
- Track shared police pressure per city
- Modify success chances globally
- Decay over time

**Core Data**
- city_id
- heat_level
- heat_modifier
- last_updated_at

**Actions**
- Increase heat on crimes
- Increase heat more on failures
- Apply heat modifier to actions
- Decay heat via scheduled job

## 7. Travel System
**System:** Travel

**Responsibilities**
- Move players between cities
- Enforce travel time
- Block actions while traveling

**Core Data**
- travel_id
- from_city
- to_city
- start_time
- arrival_time

**Actions**
- Start travel
- Complete travel (job)
- Cancel travel (premium)

## 8. Jail System
**System:** Jail

**Responsibilities**
- Restrict player actions
- Allow busts and bail
- Track jail timers

**Core Data**
- jail_entry_id
- sentence_seconds
- reason
- release_time

**Actions**
- Send player to jail
- Bust player out
- Reduce sentence
- Auto-release (job)

## 9. Hospital System
**System:** Hospital

**Responsibilities**
- Restrict actions after injury
- Track recovery time
- Allow early release

**Core Data**
- hospital_entry_id
- injury_type
- recovery_seconds
- release_time

**Actions**
- Hospitalize player
- Reduce time with items
- Instant release (premium)
- Auto-release (job)

## 10. Stats & Training
**System:** Stats & Skills

**Responsibilities**
- Track combat stats
- Track OC specialization skills

**Core Data**
- strength
- defense
- speed
- dexterity
- leadership
- driving
- demolition
- perception
- tech

**Actions**
- Train stat
- Spend energy
- Gain skill %

## 11. Economy System (Critical)
**System:** Global Economy

**Responsibilities**
- Track total money supply
- Apply price multipliers
- Prevent inflation

**Core Data**
- total_cash_in_circulation
- economy_state
- price_multiplier

**Actions**
- Snapshot money supply (job)
- Recalculate economy tier
- Apply modifiers to prices

## 12. Swiss Bank
**System:** Swiss Bank

**Responsibilities**
- Safe money storage
- Exclude funds from economy
- Apply interest (VIP)

**Core Data**
- swiss_balance
- deposit_fee
- withdrawal_delay

**Actions**
- Deposit funds
- Withdraw funds
- Apply interest (job)

## 13. PvP System
**System:** PvP Combat

**Responsibilities**
- Resolve player attacks
- Calculate damage
- Transfer money
- Send to hospital

**Core Data**
- attacker_id
- defender_id
- weapon
- armor
- damage_dealt

**Actions**
- Attack player
- Resolve combat
- Apply rewards/penalties

## 14. Social Systems
### Families
**Responsibilities**
- Group players
- Manage hierarchy
- Share resources

**Actions**
- Create family
- Invite member
- Promote/demote
- Deposit/withdraw family bank

### Chat & Messaging
**Responsibilities**
- Player communication
- Social coordination

**Actions**
- Send message
- Send family chat
- Send global chat

## 15. Property System
**System:** Properties

**Responsibilities**
- Ownership of land/buildings
- Passive income
- Storage & protection

**Actions**
- Purchase property
- Upgrade property
- Generate income (job)

## 16. Monetization
**System:** Premium Currency

**Responsibilities**
- Sell Gold Bars
- Enforce non-pay-to-win rules

**Actions**
- Purchase Gold Bars
- Spend Gold Bars
- Apply VIP perks

## 17. Notifications
**System:** Notifications

**Responsibilities**
- Notify players of events

**Actions**
- Send cooldown complete
- Send jail release
- Send OC alert
- Send attack alert

## Final Note for Codex
Prioritize correctness, server-side validation, and anti-cheat. All timers must be enforced server-side. Assume concurrent players and race conditions. Use Redis locks where double execution could occur.
