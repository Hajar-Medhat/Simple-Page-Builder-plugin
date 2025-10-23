# Simple Page Builder Plugin

The **Simple Page Builder** plugin allows WordPress users to automatically create pages using a secure REST API.  
It includes **API authentication**, **admin key management**, **webhook support**, and optional **rate limiting** and **logging** — all accessible through an intuitive WordPress admin dashboard.

---

##  Features

✅ Create multiple pages through a REST API endpoint  
✅ Secure API key and secret authentication  
✅ Admin dashboard in the **WordPress sidebar**  
✅ Webhook system with **“Send Test Webhook”** button  
✅ Optional rate limiting and request logging  
✅ Works locally (XAMPP, LocalWP) or on live WordPress sites  

---

##  Directory Structure

```
simple-page-builder/
│
├── simple-page-builder.php          # Main plugin file
├── includes/
│   ├── class-api-handler.php        # REST API endpoint
│   ├── class-auth.php               # API key generation & validation
│   ├── class-admin.php              # Admin dashboard (keys + webhook)
│   ├── class-logger.php             # Logging system (optional)
│   ├── class-rate-limit.php         # Rate limiting (optional)
│   ├── class-webhook.php            # Webhook sender & retry handler
│   └── helpers.php                  # Utility functions
│
└── README.md
```

---

##  Installation

1. **Download or clone** the repository:
   ```bash
   git clone https://github.com/Hajar-Medhat/Simple-Page-Builder-plugin.git
   ```

2. **Copy** the plugin folder into your WordPress installation:
   ```
   wp-content/plugins/
   ```

3. Go to your WordPress dashboard →  
   **Plugins → Installed Plugins → Activate “Simple Page Builder”**

4. A new menu item named **Page Builder** will appear in the **sidebar**.

---

##  Generating an API Key

1. Go to **Page Builder → API Keys** in your WordPress admin panel.  
2. Enter a key name (e.g., “My Integration”) and click **Generate**.  
3. Copy the **API Key** and **Secret** that appear.  
   > ⚠️ They will only be shown once — save them securely.  
4. Use these values in your REST API requests.

---

##  REST API Endpoint

**URL:**  
```
http://your-site.com/wp-json/pagebuilder/v1/create-pages
```

**Method:**  
`POST`

**Headers:**

| Header | Description |
|--------|-------------|
| `Content-Type` | `application/json` |
| `X-API-KEY` | Your generated API key |
| `X-API-SECRET` | Your generated API secret |

---

### 🧩 Example Request

```bash
curl -X POST http://localhost/wordpress/wp-json/pagebuilder/v1/create-pages   -H "Content-Type: application/json"   -H "X-API-KEY: your_key_here"   -H "X-API-SECRET: your_secret_here"   -d '{
        "pages": [
          {"title": "About Us", "content": "Our story..."},
          {"title": "Contact", "content": "Reach us here..."}
        ]
      }'
```

---

### ✅ Example Response

```json
{
  "status": "success",
  "message": "Pages created successfully.",
  "created": 2,
  "pages": [
    {
      "id": 12,
      "title": "About Us",
      "url": "http://localhost/wordpress/about-us/"
    },
    {
      "id": 13,
      "title": "Contact",
      "url": "http://localhost/wordpress/contact/"
    }
  ]
}
```

---

## 🔔 Webhook Settings

The plugin includes a **webhook system** that sends real-time notifications whenever new pages are created through the API.

### 🧠 Configure the Webhook

1. Go to **Page Builder → Webhook Settings**.  
2. Enter your **Webhook URL** (e.g. [https://webhook.site](https://webhook.site)).  
3. (Optional) Add a **Webhook Secret** for signature verification.  
4. Click **Save Webhook Settings**.

---

### 🧪 Test the Webhook

After saving your webhook settings, click the **“Send Test Webhook”** button.  
A success message will appear in WordPress:

> ✅ Test webhook sent successfully!

The payload will appear instantly in your webhook receiver.

---

###  Example Webhook Payload

```json
{
  "event": "pages_created",
  "timestamp": "2025-10-23T21:40:15+00:00",
  "request_id": "req_6717a01e1ef62",
  "api_key_name": "Demo Key",
  "total_pages": 2,
  "pages": [
    {"id": 12, "title": "About Us", "url": "http://localhost/about-us/"},
    {"id": 13, "title": "Contact", "url": "http://localhost/contact/"}
  ]
}
```

Each webhook request also includes a signature header for verification:

```
X-Webhook-Signature: <HMAC-SHA256 signature>
```

You can verify this signature using your **Webhook Secret** on your receiving server.

---

## 🧰 Troubleshooting

| Issue | Cause | Solution |
|--------|--------|-----------|
| `{"error":"Missing authentication headers."}` | Missing API headers | Add both `X-API-KEY` and `X-API-SECRET` |
| `404 Not Found` | Permalinks not refreshed | Go to **Settings → Permalinks → Save Changes** |
| Webhook not received | Invalid or unreachable URL | Test using [https://webhook.site](https://webhook.site) |
| Always returns `test_key` | Table not created yet | Activate plugin again or check DB privileges |

---

##  Local Testing Tips

You can test your REST API and webhooks locally using:
- **[LocalWP](https://localwp.com)** or **XAMPP**
- **[Webhook.site](https://webhook.site)** to capture and inspect webhook data
- **Ngrok** (optional) if you need to expose localhost to the internet:
  ```bash
  ngrok http 80
  ```

---

##  License

This plugin is licensed under the **GNU General Public License v2 (GPL-2.0)** or later.  
You are free to modify and redistribute it under the same license.

---

## Author

**Hajar Medhat**  
🔗 [GitHub Profile](https://github.com/Hajar-Medhat)
