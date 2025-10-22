# Simple Page Builder Plugin

The Simple Page Builder plugin allows WordPress users to automatically create pages using a secure REST API.  
It includes API authentication, admin key management, and support for rate limits and logging.

---

## Features

- Create multiple pages through a REST API endpoint  
- API key and secret authentication  
- Admin dashboard for managing API keys  
- Supports rate limiting and logging  
- Works locally (XAMPP) or on a live WordPress site  

---

## Directory Structure

```
simple-page-builder/
│
├── simple-page-builder.php          # Main plugin file
├── includes/
│   ├── class-api-handler.php        # REST API endpoint
│   ├── class-auth.php               # API key authentication
│   ├── class-admin.php              # Admin dashboard for key management
│   ├── class-logger.php             # Logging (optional)
│   ├── class-rate-limit.php         # Rate limiting (optional)
│   ├── class-webhook.php            # Webhook handling (optional)
│   └── helpers.php                  # Utility functions
│
└── README.md
```

---

## Installation

1. Download or clone the repository:
   ```bash
   git clone https://github.com/Hajar-Medhat/Simple-Page-Builder-plugin.git
   ```

2. Copy the folder to your WordPress plugins directory:
   ```
   wp-content/plugins/
   ```

3. In the WordPress admin panel, go to  
   **Plugins → Installed Plugins → Activate Simple Page Builder**

4. A new menu item named **Page Builder** will appear in the sidebar.

---

## Generating an API Key

1. Go to **Page Builder → API Keys** in the WordPress admin.  
2. Enter a name for your key and click **Generate**.  
3. Copy the API key and secret that appear on the screen.  
4. Use these values in your REST API requests.

---

## REST API Endpoint

**URL:**
```
http://your-site.com/wp-json/pagebuilder/v1/create-pages
```

**Method:**  
`POST`

**Headers:**
| Header | Description |
|--------|-------------|
| Content-Type | application/json |
| X-API-KEY | Your generated API key |
| X-API-SECRET | Your generated API secret |

**Request Example:**
```bash
curl -X POST http://localhost/wordpress/wp-json/pagebuilder/v1/create-pages   -H "Content-Type: application/json"   -H "X-API-KEY: your_key_here"   -H "X-API-SECRET: your_secret_here"   -d '{
        "pages": [
          {"title": "About Us", "content": "Our story..."},
          {"title": "Contact", "content": "Reach us here..."}
        ]
      }'
```

**Response Example:**
```json
{
  "status": "success",
  "message": "Pages created successfully.",
  "created": 2,
  "pages": [
    {
      "id": 45,
      "title": "About Us",
      "url": "http://localhost/wordpress/about-us/"
    },
    {
      "id": 46,
      "title": "Contact",
      "url": "http://localhost/wordpress/contact/"
    }
  ]
}
```

---

## Troubleshooting

| Issue | Cause | Solution |
|-------|--------|-----------|
| {"error":"Missing authentication headers."} | Missing API key or secret headers | Add X-API-KEY and X-API-SECRET headers |
| 404 Error | Permalinks not refreshed | Go to Settings → Permalinks → Save Changes |


---

## License

This project is licensed under the MIT License.

---

## Author

**Hajar Medhat**  
[GitHub Profile](https://github.com/Hajar-Medhat)
