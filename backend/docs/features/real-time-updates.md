# Real-Time Bus Movement Feature

## Overview

The real-time bus movement feature provides smooth, animated bus movement on the map based on crowd-sourced driver GPS locations. Bus markers glide smoothly to new positions instead of teleporting instantly.

## How It Works

### Data Flow (Driver App)

```
Driver GPS Location (background, every ~5-10s)
    ↓
Driver sends to location API
    ↓
Location stored in `locations` table
    ↓
Reverb broadcasts to all subscribers
    ↓
Frontend receives and animates marker
```

### Rate Limiting

- **Backend**: Locations batched and sent every ~10 seconds
- **Frontend**: Animations complete in 3 seconds (smooth interpolation)

This provides a "live movement" feel without overwhelming the server.

## OSRM Road Distance Calculation

### Overview

The system uses OSRM (Open Source Routing Machine) to calculate actual road distance between the bus and upcoming stops.

### API Endpoint

```
GET /api/route/distance?lat=X&lng=Y&stop_id=Z
```

Returns road distance in meters.

### Use Cases

1. **Stop ETA**: Calculate how far the bus is from each upcoming stop
2. **Arrival Prediction**: Show "arriving in X min" based on average speed
3. **Stop Approach State**: Mark stop as "approaching" when within 200m road distance

## Animation Engine

### Configuration

Located in [index.blade.php](../resources/views/index.blade.php):

```javascript
const ANIMATION_CONFIG = {
    duration: 1000,        // 1 second animation
    easing: 'easeInOutCubic', // Smooth acceleration/deceleration
    maxQueueSize: 2,       // Handle rapid updates
    jumpThreshold: 0.001   // Jump if distance >110m
};
```

### Easing Function

The `easeInOutCubic` function provides natural vehicle movement:
- Starts slowly (acceleration)
- Moves faster in the middle
- Ends slowly (deceleration)

### Edge Case Handling

| Situation | Behavior |
|-----------|----------|
| Distance >500m | Direct teleport (no animation) |
| Rapid updates | Queue system (max 2) |
| Multiple buses | Independent animations |
| Tab hidden | Pause animations, resume on show |
| Page unload | Cancel all animations |

## Performance

### Browser Performance

- **60fps animations** using `requestAnimationFrame`
- **Automatic throttling** on slow devices
- **Tab visibility** - pauses when tab hidden
- **Memory cleanup** - proper animation frame cancellation

### Server Performance

- **Rate limiting** prevents excessive calculations
- **Batch operations** for cleanup jobs
- **Efficient broadcasting** via Laravel Reverb

## Frontend Implementation

### Animation Loop

```javascript
function animateBusMovement(busId) {
    const state = busAnimationState[busId];
    const progress = (now - state.startTime) / state.duration;
    const easedProgress = easeInOutCubic(progress);
    const newPos = interpolate(start, end, easedProgress);
    busMarker.setLatLng([newPos.lat, newPos.lng]);

    if (progress < 1) {
        requestAnimationFrame(() => animateBusMovement(busId));
    } else {
        completeAnimation(busId);
    }
}
```

### State Management

Each bus maintains its own animation state:

```javascript
busAnimationState[busId] = {
    isAnimating: true/false,
    currentPos: { lat, lng },
    targetPos: { lat, lng },
    startTime: timestamp,
    duration: milliseconds,
    animationFrame: frameId,
    queuedUpdates: []
};
```

## Backend Implementation

### Rate Limit Configuration

[LocationController.php](../app/Http/Controllers/Api/LocationController.php):

```php
if (Cache::add($lockKey, true, 1)) { // 1 second rate limit
    CalculateBusLocationJob::dispatch($busId);
}
```

### Location Calculation

[CalculateBusLocationJob](../app/Jobs/CalculateBusLocationJob.php):

1. Fetches user locations from last 120 seconds
2. Filters by route proximity (within 100m)
3. Takes top 15 most recent locations
4. Calculates average position
5. Broadcasts update via Reverb

## Testing

### Visual Test

1. Open the map in a browser
2. Click "I'm on this bus" for any bus
3. Watch as other users send location updates
4. Bus marker should glide smoothly to new positions

### Network Test

1. Open DevTools Network tab
2. Filter by `BusLocationUpdated`
3. Verify events arrive every ~1 second
4. Verify animation completes before next update

## Troubleshooting

### Bus Marker Not Moving

**Check**:
1. Is Reverb running? `ps aux | grep reverb`
2. Are Echo listeners subscribed? Check console logs
3. Is `busAnimationState` populated?
4. Check for JavaScript errors

### Animation Jumps/Twitches

**Check**:
1. Are updates arriving too rapidly? (Should be ~1 second apart)
2. Is the queue size too small? Increase `maxQueueSize`
3. Is there a large distance jump? Check `jumpThreshold`

### High CPU Usage

**Check**:
1. How many buses are animating simultaneously?
2. Reduce `duration` to make animations faster
3. Verify tab visibility handling works
