#!/bin/bash

# Interactive script to build and install BUBT apps to a connected Android device
# Usage: ./install-device.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

print_header() {
    echo ""
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}  BUBT Bus Tracker - App Installer${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo ""
}

check_device() {
    echo -e "${YELLOW}Checking for connected devices...${NC}"
    DEVICE_COUNT=$(adb devices | grep -v "List" | grep -v "^$" | wc -l | tr -d ' ')

    if [ "$DEVICE_COUNT" -eq 0 ]; then
        echo -e "${RED}✗ No devices found. Please connect a device and enable USB debugging.${NC}"
        exit 1
    fi

    DEVICE_ID=$(adb devices | grep -v "List" | grep -v "^$" | head -1 | awk '{print $1}')
    DEVICE_STATUS=$(adb devices | grep -v "List" | grep -v "^$" | head -1 | awk '{print $2}')

    if [ "$DEVICE_STATUS" != "device" ]; then
        echo -e "${RED}✗ Device $DEVICE_ID is not authorized. Please accept the USB debugging prompt on your device.${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ Device found: $DEVICE_ID${NC}"
    echo ""
}

show_menu() {
    echo -e "${YELLOW}Which app(s) to build & install?${NC}"
    echo "  1) Driver (BUBT Driver)"
    echo "  2) Student (BUBT Tracker)"
    echo "  3) Both"
    echo ""
    read -p "Enter choice [1-3]: " choice
    echo ""
}

build_and_install() {
    local app_name=$1
    local npm_sync_cmd=$2
    local gradle_dir=$3
    local apk_output=$4
    local apk_dest=$5
    local package_name=$6

    echo -e "${CYAN}>>> Building $app_name (production)...${NC}"
    echo ""

    # Step 1: Sync icons + build Vue + copy assets
    echo -e "${YELLOW}[1/3] Syncing web assets...${NC}"
    $npm_sync_cmd

    # Step 2: Build APK with Gradle
    echo ""
    echo -e "${YELLOW}[2/3] Building APK with Gradle...${NC}"
    cd "$SCRIPT_DIR/$gradle_dir"
    ./gradlew assembleDebug
    cd "$SCRIPT_DIR"

    # Copy APK
    cp "$SCRIPT_DIR/$gradle_dir/app/build/outputs/apk/debug/app-debug.apk" "$SCRIPT_DIR/$apk_dest"

    # Step 3: Install to device
    echo ""
    echo -e "${YELLOW}[3/3] Installing to device...${NC}"
    adb install -r "$SCRIPT_DIR/$apk_dest"

    echo ""
    echo -e "${GREEN}✓ $app_name installed successfully!${NC}"
    echo -e "  Package: $package_name"
    echo -e "  APK: $apk_dest"
    echo ""
}

# Main
print_header
check_device
show_menu

case $choice in
    1)
        build_and_install \
            "Driver (BUBT Driver)" \
            "npm run cap:sync:driver:prod" \
            "capacitor-driver/android" \
            "app-debug.apk" \
            "dist-driver-prod.apk" \
            "com.bustracker.driver"
        ;;
    2)
        build_and_install \
            "Student (BUBT Tracker)" \
            "npm run cap:sync:student:prod" \
            "capacitor-student/android" \
            "app-debug.apk" \
            "dist-student-prod.apk" \
            "com.bustracker.student"
        ;;
    3)
        build_and_install \
            "Driver (BUBT Driver)" \
            "npm run cap:sync:driver:prod" \
            "capacitor-driver/android" \
            "app-debug.apk" \
            "dist-driver-prod.apk" \
            "com.bustracker.driver"

        echo -e "${CYAN}----------------------------------------${NC}"
        echo ""

        build_and_install \
            "Student (BUBT Tracker)" \
            "npm run cap:sync:student:prod" \
            "capacitor-student/android" \
            "app-debug.apk" \
            "dist-student-prod.apk" \
            "com.bustracker.student"
        ;;
    *)
        echo -e "${RED}Invalid choice. Exiting.${NC}"
        exit 1
        ;;
esac

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  All done!${NC}"
echo -e "${GREEN}========================================${NC}"
