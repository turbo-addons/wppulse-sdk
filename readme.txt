# 🧠 WPPulse Plugin Analytics Engine SDK – Integration Guide

## 🔹 Overview
**WPPulse SDK** lets you collect activation, deactivation, update, and uninstall analytics from any WordPress plugin or theme — just like Appsero — but completely self-hosted on your own server.

You’ll first set up the main analytics plugin on your server, then install the SDK inside any plugin/theme you want to track.

---

## ⚙️ STEP 1: Install WPPulse – Plugin Analytics Engine
1. Download or install **WPPulse – Plugin Analytics Engine** on your **main domain/server**.  
   Example: `https://wp-turbo.com`  
2. Activate it from your WordPress admin dashboard.  
3. After activation, you’ll see a new menu:  
   **WPPulse Analytics → Dashboard**  
4. Inside that page, you’ll find your **Endpoint URL**, like:
   ```
   https://wp-turbo.com/wp-json/wppulse/v1/collect
   ```

👉 Copy this endpoint — you’ll need it in Step 4.

---

## 📦 STEP 2: Add the SDK to Your Plugin/Theme
You can download the SDK manually or via Git:

```bash
git clone https://github.com/turbo-addons/WPPULSE---Plugin-Analytics-Engine-SDK.git
```

Then make sure your plugin or theme folder looks like this:

```
your-plugin/
├─ your-plugin.php
└─ sdk/
   └─ wppulse-plugin-analytics-engine-sdk.php
```

---

## 🧩 STEP 3: Integrate SDK in Your Plugin or Theme
Open your plugin’s main file (right below the plugin header)  
and add this code snippet 👇

```php
/**
 * -------------------------------------------------
 * ✅ WPPulse SDK Integration (Plugin Analytics)
 * -------------------------------------------------
 */

// ✅ Include SDK
require_once __DIR__ . '/wppulse-sdk/wppulse-plugin-analytics-engine-sdk.php';
require_once __DIR__ . '/wppulse-sdk/wppulse-plugin-updater.php';

// ✅ Fetch plugin data automatically
$plugin_data = get_file_data( __FILE__, [
    'Name'       => 'Plugin Name',
    'Version'    => 'Version',
    'TextDomain' => 'Text Domain',
] );

$plugin_slug = dirname( plugin_basename( __FILE__ ) );


if ( class_exists( 'WPPulse_Plugin_Updater' ) ) {
    new WPPulse_Plugin_Updater(
        __FILE__,
        $plugin_slug,
        $plugin_data['Version'],
        ''
    );
}

// ✅ Initialize SDK
if ( class_exists( 'WPPulse_SDK' ) ) {
    WPPulse_SDK::init( __FILE__, [
        'name'     => $plugin_data['Name'],
        'slug'     => $plugin_slug,
        'version'  => $plugin_data['Version'],
        'endpoint' => '',
    ] );
}
```

> ⚠️ Make sure you replace the `'endpoint'` with your own main site’s WPPulse endpoint.

---

## 🧪 STEP 4: Verify the Connection
After adding and activating your client plugin:

1. Go to your main site (where WPPulse – Plugin Analytics Engine is installed).  
2. Open **WPPulse Analytics → Dashboard**.  
3. You’ll now see your plugin name appear automatically with its events.

---

## 📊 STEP 5: What Gets Tracked
The SDK automatically sends these analytics events:

| Event | Description |
|--------|--------------|
| **activated** | When user activates your plugin |
| **deactivated** | When user deactivates (includes feedback modal) |
| **updated** | When plugin updates |
| **uninstalled** | When plugin deleted |

### Example Payload (JSON)
```json
{
  "domain": "https://client-site.com",
  "email": "admin@client-site.com",
  "plugin": "Whitespace Fixer for XML Sitemap",
  "version": "1.0.1",
  "status": "deactivated",
  "reason_id": "missing-feature",
  "reason_text": "Need support for multisite"
}
```

---

## 🧭 STEP 6: Deactivation Feedback Modal
When a user clicks **Deactivate** in the plugins list,  
a modern feedback popup appears asking for the reason.  
It looks like this:

- ❓ Couldn’t understand  
- 🏆 Found a better plugin  
- 🧰 Missing a feature  
- ❌ Not working  
- 🔍 Not what I was looking for  
- ⚠️ Didn’t work as expected  
- ⋯ Others  

Users can either **Submit & Deactivate** or **Skip & Deactivate**,  
and both actions get tracked to your analytics dashboard.

---

## 🔐 STEP 7: Security Recommendations
To keep your analytics secure:
- Only accept data from **allowed domains** (whitelist).
- Optionally add an **HMAC signature** or **secret token** check on your endpoint.
- Log IP or timestamps to prevent abuse.
- Always validate incoming JSON server-side.

---

## ✅ STEP 8: Testing Checklist
| Test | Expected Result |
|------|-----------------|
| Plugin Activated | “activated” event appears in WPPulse Dashboard |
| Plugin Deactivated | Modal appears, “deactivated” event logged |
| Submitted Feedback | `reason_id` and `reason_text` saved |
| Plugin Updated | “updated” event logged |
| Plugin Deleted | “uninstalled” event logged |

---

## 🧰 Troubleshooting
**Issue:** SDK not found  
→ Check the file path: `/sdk/wppulse-plugin-analytics-engine-sdk.php`

**Issue:** No data on dashboard  
→ Verify the endpoint URL is correct and server accepts POST requests.

**Issue:** 403/401 unauthorized  
→ If you added security (token/signature), ensure the client includes it.

---

## 🪪 License
WPPulse SDK is **open-source (GPL-compatible)** — you can modify or extend it freely.

---

### 🚀 Summary
✅ Install **WPPulse – Plugin Analytics Engine** on your main site  
✅ Copy the SDK into your client plugin/theme’s `/sdk/` folder  
✅ Add the initialization snippet with your **endpoint URL**  
✅ Activate the client plugin — analytics will appear instantly in your WPPulse dashboard 🎯
