# Real-Time Bus Movement Feature

## Overview

The real-time bus movement feature provides smooth, animated bus movement on the map based on driver GPS locations. Bus markers glide smoothly to new positions instead of teleporting instantly.

## How It Works

### Data Flow (Driver App)

```
Driver GPS Location (background, every ~5-10s)
    ↓
Driver sends to /api/driver/location
    ↓
Location stored in `locations` table
    ↓
Reverb broadcasts BusLocationUpdated to all subscribers
    ↓
Frontend receives and animates marker
```

### Rate Limiting

- **Driver App**: Sends locations every ~5-10 seconds
- **Frontend**: Animations complete in 1 second (smooth interpolation)

This provides a "live movement" feel without excessive server load.

## Animation Engine

### Configuration

Located in Vue map components:

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

- **No rate limiting on driver locations** - trusted device
- **Efficient broadcasting** via Laravel Reverb
- **Location data cleanup** via scheduled jobs

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

### Location Controller

[LocationController.php](../app/Http/Controllers/Api/Driver/LocationController.php):

```php
public function update(Request $request)
{
    // Validates driver authentication
    // Gets active trip
    // Stores location to Location model
    // Updates trip's current coordinates
    // Broadcasts BusLocationUpdated via Reverb
}
```

### Location Storage

1. Driver sends location via `POST /api/driver/location`
2. Location stored in `locations` table with trip_id
3. Trip record updated with current_lat, current_lng
4. BusLocationUpdated event broadcast via Reverb

## Testing

### Visual Test

1. Open the student app in a browser
2. View active trips on the map
3. Watch as driver sends location updates
4. Bus marker should glide smoothly to new positions

### Network Test

1. Open DevTools Network tab
2. Filter by `BusLocationUpdated`
3. Verify events arrive as driver sends updates
4. Verify animation completes before next update

## Troubleshooting

### Bus Marker Not Moving

**Check**:
1. Is Reverb running? `ps aux | grep reverb`
2. Are Echo listeners subscribed? Check console logs
3. Is `busAnimationState` populated?
4. Check for JavaScript errors
5. Is the driver actively sending locations?

### Animation Jumps/Twitches

**Check**:
1. Are updates arriving too rapidly? (Should be ~5-10 seconds apart)
2. Is the queue size too small? Increase `maxQueueSize`
3. Is there a large distance jump? Check `jumpThreshold`

### High CPU Usage

**Check**:
1. How many buses are animating simultaneously?
2. Reduce `duration` to make animations faster
3. Verify tab visibility handling works
