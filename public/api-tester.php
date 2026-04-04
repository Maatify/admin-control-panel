<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load ENV safely (same style as public/index.php)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$appEnv = $_ENV['APP_ENV'] ?? $_ENV['APP_DEBUG_ENV'] ?? 'local';
$isLocalAllowed = in_array($appEnv, ['local', 'development', 'dev'], true);

// Optional extra restriction for localhost access only
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalRequest = in_array($remoteAddr, ['127.0.0.1', '::1', 'localhost'], true);

// You can relax this if needed, but safer to keep both checks.
if (!$isLocalAllowed || !$isLocalRequest) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'API Tester is disabled outside local development.';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local API Tester</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .title {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }

        .subtitle {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 180px 1fr 140px;
            gap: 12px;
            align-items: center;
        }

        .row {
            margin-top: 16px;
        }

        .row label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #cbd5e1;
        }

        input,
        select,
        textarea,
        button {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #0b1220;
            color: #e2e8f0;
            padding: 12px 14px;
            font-size: 14px;
        }

        textarea {
            min-height: 220px;
            resize: vertical;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        button {
            cursor: pointer;
            background: #2563eb;
            border-color: #2563eb;
            font-weight: 700;
        }

        button:hover {
            background: #1d4ed8;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .actions button.secondary {
            background: #1f2937;
            border-color: #334155;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(120px, 1fr));
            gap: 12px;
        }

        .meta-box {
            background: #0b1220;
            border: 1px solid #1f2937;
            border-radius: 10px;
            padding: 12px;
        }

        .meta-box .label {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .meta-box .value {
            font-size: 14px;
            font-weight: 700;
            word-break: break-word;
        }

        .hint {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.6;
        }

        .response-area {
            min-height: 320px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .success {
            color: #22c55e;
        }

        .warning {
            color: #f59e0b;
        }

        .error {
            color: #ef4444;
        }

        .small {
            font-size: 12px;
            color: #94a3b8;
        }

        @media (max-width: 900px) {
            .grid,
            .meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1 class="title">Local API Tester</h1>
        <p class="subtitle">
            Standalone page under <code>/public</code> for testing real session-based endpoints from the same browser.
        </p>
    </div>

    <div class="card">
        <div class="grid">
            <div>
                <label for="method">Method</label>
                <select id="method">
                    <option value="GET">GET</option>
                    <option value="POST" selected>POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>

            <div>
                <label for="endpoint">Endpoint</label>
                <input
                    id="endpoint"
                    type="text"
                    value="/api/sessions/query"
                    placeholder="/api/your-endpoint"
                >
            </div>

            <div>
                <label>&nbsp;</label>
                <button id="sendButton" type="button">Send Request</button>
            </div>
        </div>

        <div class="row">
            <label for="headers">Headers (JSON object)</label>
            <textarea id="headers" spellcheck="false">{
  "Content-Type": "application/json",
  "Accept": "application/json"
}</textarea>
        </div>

        <div class="row">
            <label for="body">Body (JSON object)</label>
            <textarea id="body" spellcheck="false">{
  "page": 1,
  "per_page": 10
}</textarea>
        </div>

        <div class="row actions">
            <button id="formatBodyButton" type="button" class="secondary">Format Body JSON</button>
            <button id="formatHeadersButton" type="button" class="secondary">Format Headers JSON</button>
            <button id="clearResponseButton" type="button" class="secondary">Clear Response</button>
            <button id="clearHistoryButton" type="button" class="secondary">Clear History</button>
        </div>

        <div class="row">
            <p class="hint">
                Allowed use is intended for same-origin local endpoints such as <code>/api/...</code>.
                Since this page runs on the same origin, the browser automatically sends your current session cookie with the request.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="meta">
            <div class="meta-box">
                <span class="label">Status</span>
                <span class="value" id="responseStatus">-</span>
            </div>
            <div class="meta-box">
                <span class="label">Duration</span>
                <span class="value" id="responseDuration">-</span>
            </div>
            <div class="meta-box">
                <span class="label">Content-Type</span>
                <span class="value" id="responseContentType">-</span>
            </div>
            <div class="meta-box">
                <span class="label">Endpoint</span>
                <span class="value" id="responseEndpoint">-</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="row">
            <label for="responseHeaders">Response Headers</label>
            <textarea id="responseHeaders" class="response-area" readonly spellcheck="false"></textarea>
        </div>

        <div class="row">
            <label for="responseBody">Response Body</label>
            <textarea id="responseBody" class="response-area" readonly spellcheck="false"></textarea>
        </div>

        <div class="row small">
            This page is intentionally standalone and does not depend on project routes.
        </div>
    </div>
</div>

<script>

    // ===============================
    // 🔥 History + Presets
    // ===============================

    const HISTORY_KEY = 'apiTesterHistory';
    const MAX_HISTORY = 10;

    // Presets جاهزة
    const PRESETS = [
        {
            name: 'Sessions Query',
            method: 'POST',
            endpoint: '/api/sessions/query',
            body: {
                page: 1,
                per_page: 10
            }
        },
        {
            name: 'Admins Query',
            method: 'POST',
            endpoint: '/api/admins/query',
            body: {
                page: 1,
                per_page: 10
            }
        },
        {
            name: 'Currency Query',
            method: 'POST',
            endpoint: '/api/currencies/query',
            body: {
                page: 1,
                per_page: 10
            }
        }
    ];

    const methodElement = document.getElementById('method');
    const endpointElement = document.getElementById('endpoint');
    const headersElement = document.getElementById('headers');
    const bodyElement = document.getElementById('body');
    const sendButtonElement = document.getElementById('sendButton');
    const formatBodyButtonElement = document.getElementById('formatBodyButton');
    const formatHeadersButtonElement = document.getElementById('formatHeadersButton');
    const clearResponseButtonElement = document.getElementById('clearResponseButton');
    const clearHistoryButtonElement = document.getElementById('clearHistoryButton');

    const responseStatusElement = document.getElementById('responseStatus');
    const responseDurationElement = document.getElementById('responseDuration');
    const responseContentTypeElement = document.getElementById('responseContentType');
    const responseEndpointElement = document.getElementById('responseEndpoint');
    const responseHeadersElement = document.getElementById('responseHeaders');
    const responseBodyElement = document.getElementById('responseBody');

    function setResponseMeta(statusText, durationText, contentTypeText, endpointText, statusClassName = '') {
        responseStatusElement.textContent = statusText;
        responseStatusElement.className = 'value ' + statusClassName;
        responseDurationElement.textContent = durationText;
        responseContentTypeElement.textContent = contentTypeText;
        responseEndpointElement.textContent = endpointText;
    }

    function formatJsonTextarea(textareaElement) {
        const rawValue = textareaElement.value.trim();

        if (rawValue === '') {
            textareaElement.value = '';
            return;
        }

        try {
            const parsedValue = JSON.parse(rawValue);
            textareaElement.value = JSON.stringify(parsedValue, null, 2);
        } catch (error) {
            alert('Invalid JSON:\n' + error.message);
        }
    }

    function tryParseJsonText(value) {
        const trimmedValue = value.trim();

        if (trimmedValue === '') {
            return null;
        }

        return JSON.parse(trimmedValue);
    }

    function stringifyHeaders(headers) {
        const headersObject = {};

        headers.forEach((value, key) => {
            headersObject[key] = value;
        });

        return JSON.stringify(headersObject, null, 2);
    }

    function getStatusClassName(statusCode) {
        if (statusCode >= 200 && statusCode < 300) {
            return 'success';
        }

        if (statusCode >= 400) {
            return 'error';
        }

        return 'warning';
    }

    function saveHistoryItem(method, endpoint, body) {
        try {
            let history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');

            // 🔥 remove duplicates first
            history = history.filter(item =>
                !(
                    item.method === method &&
                    item.endpoint === endpoint &&
                    JSON.stringify(item.body) === JSON.stringify(body)
                )
            );

            // ثم أضف الجديد في الأول
            history.unshift({ method, endpoint, body });

            if (history.length > 10) {
                history = history.slice(0, 10);
            }

            localStorage.setItem(HISTORY_KEY, JSON.stringify(history));

            renderHistory();
        } catch (e) {
            console.warn('History save failed', e);
        }
    }

    async function sendRequest(saveHistory = true) {
        const method = methodElement.value.trim().toUpperCase();
        const endpoint = endpointElement.value.trim();

        if (endpoint === '') {
            alert('Please enter an endpoint.');
            return;
        }

        // 🔒 Security check
        if (!endpoint.startsWith('/api/')) {
            alert('Only /api/* endpoints are allowed');
            return;
        }

        let headersObject = {};
        let parsedBody = null;

        try {
            const parsedHeaders = tryParseJsonText(headersElement.value);
            if (parsedHeaders !== null) {
                headersObject = parsedHeaders;
            }
        } catch (error) {
            alert('Headers JSON is invalid:\n' + error.message);
            return;
        }

        try {
            parsedBody = tryParseJsonText(bodyElement.value);
        } catch (error) {
            alert('Body JSON is invalid:\n' + error.message);
            return;
        }

        if (saveHistory) {
            saveHistoryItem(method, endpoint, parsedBody);
        }

        const fetchOptions = {
            method: method,
            credentials: 'same-origin',
            headers: headersObject
        };

        if (!['GET', 'HEAD'].includes(method)) {
            fetchOptions.body = parsedBody === null ? '' : JSON.stringify(parsedBody);
        }

        setResponseMeta('Loading...', '-', '-', endpoint, 'warning');
        responseHeadersElement.value = '';
        responseBodyElement.value = '';

        const startedAt = performance.now();

        try {
            const response = await fetch(endpoint, fetchOptions);
            const endedAt = performance.now();
            const duration = Math.round(endedAt - startedAt) + ' ms';

            const responseContentType = response.headers.get('content-type') || '-';
            const responseText = await response.text();

            let formattedResponseBody = responseText;

            if (responseText.trim() !== '' && responseContentType.includes('application/json')) {
                try {
                    const parsedResponseJson = JSON.parse(responseText);
                    formattedResponseBody = JSON.stringify(parsedResponseJson, null, 2);
                } catch (error) {}
            }

            setResponseMeta(
                response.status + ' ' + response.statusText,
                duration,
                responseContentType,
                endpoint,
                getStatusClassName(response.status)
            );

            responseHeadersElement.value = stringifyHeaders(response.headers);
            responseBodyElement.value = formattedResponseBody;

        } catch (error) {
            const endedAt = performance.now();
            const duration = Math.round(endedAt - startedAt) + ' ms';

            setResponseMeta('Request Failed', duration, '-', endpoint, 'error');
            responseHeadersElement.value = '';
            responseBodyElement.value = error.message;
        }
    }

    sendButtonElement.addEventListener('click', sendRequest);

    formatBodyButtonElement.addEventListener('click', function () {
        formatJsonTextarea(bodyElement);
    });

    formatHeadersButtonElement.addEventListener('click', function () {
        formatJsonTextarea(headersElement);
    });

    clearResponseButtonElement.addEventListener('click', function () {
        setResponseMeta('-', '-', '-', '-', '');
        responseHeadersElement.value = '';
        responseBodyElement.value = '';
    });

    clearHistoryButtonElement.addEventListener('click', function () {
        localStorage.removeItem(HISTORY_KEY);
        renderHistory();
    });



    // // ===============================
    // // 🔒 Security Guard
    // // ===============================
    // function validateEndpoint(endpoint) {
    //     if (!endpoint.startsWith('/api/')) {
    //         throw new Error('Only /api/* endpoints are allowed');
    //     }
    // }

    // ===============================
    // 💾 History Functions
    // ===============================
    function loadHistory() {
        return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
    }

    // ===============================
    // 🖥 Render History
    // ===============================
    function renderHistory() {
        const history = loadHistory();

        let container = document.getElementById('historyContainer');

        if (!container) {
            container = document.createElement('div');
            container.id = 'historyContainer';
            container.className = 'card';

            container.innerHTML = `
            <h3>History</h3>
            <div id="historyList"></div>
        `;

            document.querySelector('.container').appendChild(container);

        }

        const list = document.getElementById('historyList');

        list.innerHTML = '';

        if (history.length === 0) {
            list.innerHTML = '<p class="small">No history yet</p>';
            return;
        }

        history.forEach((item, index) => {
            const btn = document.createElement('button');
            btn.className = 'secondary history-btn';
            btn.style.width = '100%';
            btn.style.marginBottom = '8px';

            btn.dataset.index = index;
            btn.textContent = `${item.method} ${item.endpoint}`;

            list.appendChild(btn);
        });

        // 🔥 event delegation
        list.onclick = function (e) {
            const target = e.target.closest('.history-btn');

            if (!target) return;

            const index = parseInt(target.dataset.index, 10);
            const item = history[index];

            if (!item) {
                console.error('History item not found');
                return;
            }

            console.log('loading history item:', item);

            // 🔥 remove highlight من الكل
            document.querySelectorAll('.history-btn').forEach(btn => {
                btn.style.border = '1px solid #334155';
            });

            // 🔥 highlight الحالي
            target.style.border = '2px solid #2563eb';

            document.getElementById('method').value = item.method || 'POST';
            document.getElementById('endpoint').value = item.endpoint || '';
            document.getElementById('body').value = item.body
                ? JSON.stringify(item.body, null, 2)
                : '';

            sendRequest(false); // 🔥 auto execute
        };
    }

    // ===============================
    // 🧩 Render Presets
    // ===============================
    function renderPresets() {
        let container = document.createElement('div');
        container.className = 'card';

        container.innerHTML = `
        <h3>Presets</h3>
        <div id="presetsList"></div>
    `;

        document.querySelector('.container').appendChild(container);

        const list = document.getElementById('presetsList');

        PRESETS.forEach(preset => {
            const btn = document.createElement('button');
            btn.className = 'secondary';
            btn.style.marginBottom = '8px';
            btn.textContent = preset.name;

            btn.onclick = () => {
                methodElement.value = preset.method;
                endpointElement.value = preset.endpoint;
                bodyElement.value = JSON.stringify(preset.body, null, 2);
            };

            list.appendChild(btn);
        });
    }

    // ===============================
    // 🚀 Init
    // ===============================
    renderHistory();
    renderPresets();
</script>


</body>
</html>