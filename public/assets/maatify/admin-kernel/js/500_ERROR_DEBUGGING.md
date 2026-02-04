# 🔥 500 Internal Server Error - Full HTML Display

## 🎯 Problem

When backend returns **500 Internal Server Error** with an **HTML error page**, you need to see the FULL HTML to debug the PHP/backend error.

---

## ✅ Solution

The enhanced `ApiHandler` now:
1. ✅ Logs the **full raw response** (even if HTML)
2. ✅ Includes `rawBody` in error result
3. ✅ Shows HTML error page in console

---

## 📊 Console Output for 500 Error

### Step 1: Request Details
```
📤 [Query Languages] Request Details
  Timestamp: 2025-02-04T12:34:56.789Z
  Endpoint: languages/query
  Payload: {page: 1, per_page: 25}
  
🌐 [Query Languages] Full URL: /api/languages/query
🌐 [Query Languages] Method: POST
```

---

### Step 2: Response Headers
```
📡 [Query Languages] Response Details
  Status: 500 Internal Server Error
  OK: false
  Type: basic
  URL: http://localhost:8080/api/languages/query
  
  Headers:
  ┌─────────────────┬──────────────────────────┐
  │     (index)     │          Values          │
  ├─────────────────┼──────────────────────────┤
  │ content-type    │ 'text/html'              │
  │ content-length  │ '2456'                   │
  │ date            │ 'Tue, 04 Feb 2025...'   │
  │ server          │ 'nginx/1.18.0'          │
  └─────────────────┴──────────────────────────┘
```

**Key indicator:** `content-type: text/html` = Error page!

---

### Step 3: Raw HTML Body
```
📄 [Query Languages] Raw Response Body
  Body: <!DOCTYPE html>
<html>
<head>
    <title>500 Internal Server Error</title>
</head>
<body>
    <h1>Whoops, looks like something went wrong.</h1>
    
    <h2>Fatal error: Uncaught TypeError: 
    Call to undefined method App\Services\LanguageService::getAll()
    in /var/www/html/app/Controllers/LanguageController.php:45
    </h2>
    
    <pre>
    Stack trace:
    #0 /var/www/html/vendor/slim/slim/Slim/Handlers/Strategies/RequestResponse.php(40): 
       App\Controllers\LanguageController->query()
    #1 /var/www/html/vendor/slim/slim/Slim/Routing/Route.php(381): 
       Slim\Handlers\Strategies\RequestResponse->__invoke()
    ...
    </pre>
</body>
</html>
  
  Length: 2456 characters
  First 200 chars: <!DOCTYPE html>
<html>
<head>
    <title>500 Internal Server Error</title>
</head>
<body>
    <h1>Whoops, looks like something went wrong.</h1>
    
    <h2>Fatal error: ...
  
  ⚠️ Content appears to be HTML (possibly an error page)
```

**Now you can see:**
- ✅ The exact PHP error
- ✅ The file and line number
- ✅ The full stack trace

---

### Step 4: JSON Parse Failed
```
❌ [Query Languages] JSON Parse Failed
  Parse Error: Unexpected token '<' at position 0
  Error Stack: SyntaxError: Unexpected token '<'
    at JSON.parse (<anonymous>)
    at parseResponse (api_handler.js:99)
  
  Raw text that failed to parse: <!DOCTYPE html>
<html>
<head>
    <title>500 Internal Server Error</title>
...
```

---

### Step 5: HTTP Error Summary
```
❌ [Query Languages] HTTP Error 500
  Status: 500 Internal Server Error
  Data: null
  Raw Body (HTML/Text): <!DOCTYPE html>
<html>
<head>
    <title>500 Internal Server Error</title>
</head>
<body>
    <h1>Whoops, looks like something went wrong.</h1>
    
    <h2>Fatal error: Uncaught TypeError: 
    Call to undefined method App\Services\LanguageService::getAll()
    in /var/www/html/app/Controllers/LanguageController.php:45
    </h2>
    ...
</body>
</html>
```

**Key:** The FULL HTML is logged here!

---

### Step 6: Final Result
```
📊 [Query Languages] Final Result
  Success: false
  Error: HTTP 500: Internal Server Error
  Data: null
  Raw Body: <!DOCTYPE html>...[full HTML]...
  Status: 500

⏱️ [Query Languages] Duration: 234.56ms
```

---

## 🔍 How to Use the HTML Error

### 1. **Copy the Raw Body**
Right-click on the Raw Body log → Copy string contents

### 2. **Save as HTML File**
```bash
# Save to file
echo "<!DOCTYPE html>..." > error.html

# Open in browser
open error.html
```

### 3. **Extract Key Info**

Look for:
```html
<h2>Fatal error: Uncaught TypeError</h2>
```

**This tells you:**
- Error type: `TypeError`
- Message: `Call to undefined method`
- File: `/var/www/html/app/Controllers/LanguageController.php`
- Line: `45`

### 4. **Check Stack Trace**
```html
<pre>
Stack trace:
#0 /var/www/html/vendor/slim/slim/...
#1 /var/www/html/app/Controllers/...
</pre>
```

**This shows:**
- Execution path
- Which methods were called
- Where the error originated

---

## 🐛 Common 500 Errors

### Error 1: Undefined Method
```
Fatal error: Call to undefined method ClassName::methodName()
```

**Fix:** Check if method exists, or if class is imported correctly.

---

### Error 2: Syntax Error
```
Parse error: syntax error, unexpected '}' in file.php on line 123
```

**Fix:** Check PHP syntax in the specified file.

---

### Error 3: Database Connection
```
PDOException: SQLSTATE[HY000] [2002] Connection refused
```

**Fix:** Check database credentials, ensure DB server is running.

---

### Error 4: Missing Class
```
Fatal error: Class 'App\Services\LanguageService' not found
```

**Fix:** Check autoloader, ensure class file exists.

---

### Error 5: Type Error
```
TypeError: Argument 1 passed to method() must be of type int, string given
```

**Fix:** Check parameter types in method call.

---

## 💡 Pro Tips

### Tip 1: Search for "Fatal error"
In the Raw Body, search for:
- `Fatal error:`
- `Parse error:`
- `TypeError:`
- `PDOException:`

### Tip 2: Find File and Line
Look for patterns like:
- `in /path/to/file.php:123`
- `on line 45`

### Tip 3: Check Stack Trace
The stack trace shows the execution path:
```
#0 → First call (deepest)
#1 → Second call
#2 → Third call
...
```

Read **bottom to top** to understand the flow.

### Tip 4: Copy Full HTML
Sometimes IDEs can render the HTML:
1. Copy full Raw Body
2. Paste into new HTML file
3. Open in browser
4. Better formatted error display!

---

## ✅ Success Indicators

When debugging 500 errors, you now have:

1. ✅ Full HTML error page
2. ✅ Exact error message
3. ✅ File and line number
4. ✅ Complete stack trace
5. ✅ All response headers
6. ✅ Request that caused it

**No more blind debugging!** 🎯

---

## 📋 Debugging Workflow

```
1. See "HTTP 500" error
   ↓
2. Open Console
   ↓
3. Find "📄 Raw Response Body"
   ↓
4. Read HTML error page
   ↓
5. Identify:
   - Error type
   - File location
   - Line number
   ↓
6. Check backend code
   ↓
7. Fix the bug
   ↓
8. Test again!
```

---

**Now you can see EVERYTHING when 500 errors happen!** 🔥
