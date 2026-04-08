#!/bin/zsh

set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: ./scripts/sync-android-branding.sh <driver|student>"
  exit 1
fi

APP_TYPE="$1"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
PROJECT_DIR="$ROOT_DIR/capacitor-${APP_TYPE}"
RES_DIR="$PROJECT_DIR/app/src/main/res"
ASSETS_DIR="$PROJECT_DIR/app/src/main/assets"
GENERATED_DIR="$ROOT_DIR/resources/generated/$APP_TYPE"
MASTER_ICON="$GENERATED_DIR/icon-1024.png"
SPLASH_SOURCE_SVG="$ROOT_DIR/public/icons/app-${APP_TYPE}.svg"

case "$APP_TYPE" in
  driver)
    APP_NAME="BUBT Driver"
    APP_ID="com.bustracker.driver"
    BRAND_COLOR="#059669"
    BRAND_COLOR_DARK="#047857"
    SPLASH_SOURCE_SVG="$ROOT_DIR/public/icons/splash-driver.svg"
    ;;
  student)
    APP_NAME="BUBT Tracker"
    APP_ID="com.bustracker.student"
    BRAND_COLOR="#4F46E5"
    BRAND_COLOR_DARK="#4338CA"
    ;;
  *)
    echo "Unsupported app type: $APP_TYPE"
    exit 1
    ;;
esac

if [[ ! -d "$PROJECT_DIR" ]]; then
  echo "Capacitor project not found: $PROJECT_DIR"
  echo "Create it first, then rerun this script."
  exit 1
fi

"$SCRIPT_DIR/export-app-icons.sh" "$APP_TYPE" "$GENERATED_DIR"

if [[ ! -f "$MASTER_ICON" ]]; then
  echo "Master icon not generated: $MASTER_ICON"
  exit 1
fi

mkdir -p "$RES_DIR" "$RES_DIR/values"
mkdir -p "$ASSETS_DIR"

hex_color="${BRAND_COLOR#\#}"
tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/branding-${APP_TYPE}.XXXXXX")"
trap 'rm -rf "$tmp_dir"' EXIT
SPLASH_MASTER="$tmp_dir/splash-1024.png"

if [[ ! -f "$SPLASH_SOURCE_SVG" ]]; then
  echo "Splash source not found: $SPLASH_SOURCE_SVG"
  exit 1
fi

qlmanage -t -s 1024 -o "$tmp_dir" "$SPLASH_SOURCE_SVG" >/dev/null
splash_thumbnail="$tmp_dir/$(basename "$SPLASH_SOURCE_SVG").png"

if [[ ! -f "$splash_thumbnail" ]]; then
  echo "Quick Look did not create splash PNG export for $SPLASH_SOURCE_SVG"
  exit 1
fi

mv "$splash_thumbnail" "$SPLASH_MASTER"

typeset -A launcher_sizes=(
  [mdpi]=48
  [hdpi]=72
  [xhdpi]=96
  [xxhdpi]=144
  [xxxhdpi]=192
)

typeset -A splash_sizes=(
  [drawable]="480x320"
  [drawable-port-mdpi]="480x800"
  [drawable-port-hdpi]="720x1280"
  [drawable-port-xhdpi]="960x1600"
  [drawable-port-xxhdpi]="1440x2560"
  [drawable-port-xxxhdpi]="1920x3200"
  [drawable-land-mdpi]="800x480"
  [drawable-land-hdpi]="1280x720"
  [drawable-land-xhdpi]="1600x960"
  [drawable-land-xxhdpi]="2560x1440"
  [drawable-land-xxxhdpi]="3200x1920"
)

for density size in ${(kv)launcher_sizes}; do
  launcher_dir="$RES_DIR/mipmap-$density"
  mkdir -p "$launcher_dir"

  icon_png="$GENERATED_DIR/android-$density.png"
  foreground_png="$tmp_dir/ic_launcher_foreground-$density.png"

  cp "$icon_png" "$launcher_dir/ic_launcher.png"
  cp "$icon_png" "$launcher_dir/ic_launcher_round.png"

  sips -z "$size" "$size" "$MASTER_ICON" --out "$foreground_png" >/dev/null
  cp "$foreground_png" "$launcher_dir/ic_launcher_foreground.png"
done

for drawable dimensions in ${(kv)splash_sizes}; do
  width="${dimensions%x*}"
  height="${dimensions#*x}"
  splash_dir="$RES_DIR/$drawable"
  splash_tmp="$tmp_dir/${drawable}-splash-icon.png"
  splash_size=$(( (${width} < ${height} ? ${width} : ${height}) * 38 / 100 ))

  (( splash_size < 96 )) && splash_size=96

  mkdir -p "$splash_dir"
  sips -z "$splash_size" "$splash_size" "$SPLASH_MASTER" --out "$splash_tmp" >/dev/null 2>&1
  sips --padToHeightWidth "$height" "$width" --padColor "$hex_color" "$splash_tmp" --out "$splash_dir/splash.png" >/dev/null 2>&1
done

cat > "$RES_DIR/values/strings.xml" <<EOF
<?xml version='1.0' encoding='utf-8'?>
<resources>
    <string name="app_name">$APP_NAME</string>
    <string name="title_activity_main">$APP_NAME</string>
    <string name="package_name">$APP_ID</string>
    <string name="custom_url_scheme">$APP_ID</string>
</resources>
EOF

cat > "$RES_DIR/values/ic_launcher_background.xml" <<EOF
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="ic_launcher_background">$BRAND_COLOR</color>
</resources>
EOF

cat > "$RES_DIR/values/colors.xml" <<EOF
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="colorPrimary">$BRAND_COLOR</color>
    <color name="colorPrimaryDark">$BRAND_COLOR_DARK</color>
    <color name="colorAccent">$BRAND_COLOR</color>
    <color name="statusBarColor">$BRAND_COLOR</color>
</resources>
EOF

cat > "$RES_DIR/values/styles.xml" <<EOF
<?xml version="1.0" encoding="utf-8"?>
<resources>

    <style name="AppTheme" parent="Theme.AppCompat.Light.DarkActionBar">
        <item name="colorPrimary">@color/colorPrimary</item>
        <item name="colorPrimaryDark">@color/colorPrimaryDark</item>
        <item name="colorAccent">@color/colorAccent</item>
        <item name="android:statusBarColor">@color/statusBarColor</item>
        <item name="android:windowDrawsSystemBarBackgrounds">true</item>
        <item name="android:windowLightStatusBar">false</item>
    </style>

    <style name="AppTheme.NoActionBar" parent="Theme.AppCompat.DayNight.NoActionBar">
        <item name="windowActionBar">false</item>
        <item name="windowNoTitle">true</item>
        <item name="android:background">@null</item>
        <item name="android:statusBarColor">@color/statusBarColor</item>
        <item name="android:windowDrawsSystemBarBackgrounds">true</item>
        <item name="android:windowLightStatusBar">false</item>
    </style>

    <style name="AppTheme.NoActionBarLaunch" parent="Theme.SplashScreen">
        <item name="android:background">@drawable/splash</item>
        <item name="android:statusBarColor">@color/statusBarColor</item>
        <item name="android:windowDrawsSystemBarBackgrounds">true</item>
        <item name="android:windowLightStatusBar">false</item>
    </style>
</resources>
EOF

settings_gradle="$PROJECT_DIR/capacitor.settings.gradle"
if [[ -f "$settings_gradle" ]] && ! grep -q "capacitor-status-bar" "$settings_gradle"; then
  cat >> "$settings_gradle" <<'EOF'

include ':capacitor-status-bar'
project(':capacitor-status-bar').projectDir = new File('../node_modules/@capacitor/status-bar/android')
EOF
fi

capacitor_build_gradle="$PROJECT_DIR/app/capacitor.build.gradle"
if [[ -f "$capacitor_build_gradle" ]] && ! grep -q "capacitor-status-bar" "$capacitor_build_gradle"; then
  perl -0pi -e "s/\n\}\n\n\nif \(hasProperty\('postBuildExtras'\)\) \{/\n    implementation project(':capacitor-status-bar')\n\n}\n\n\nif (hasProperty('postBuildExtras')) {/s" "$capacitor_build_gradle"
fi

plugin_json="$ASSETS_DIR/capacitor.plugins.json"
if [[ -f "$plugin_json" ]]; then
  perl -0pi -e 's/,\n\t\{\n\t\t"pkg": "\/status-bar",\n\t\t"classpath": "com\.capacitorjs\.plugins\.statusbar\.StatusBarPlugin"\n\t\}//s' "$plugin_json"

  if ! grep -q "@capacitor/status-bar" "$plugin_json"; then
    perl -0pi -e 's/\n\]\s*$/,\n\t{\n\t\t"pkg": "\@capacitor\/status-bar",\n\t\t"classpath": "com.capacitorjs.plugins.statusbar.StatusBarPlugin"\n\t}\n]\n/s' "$plugin_json"
  fi
fi

echo "Synced Android branding for $APP_TYPE"
