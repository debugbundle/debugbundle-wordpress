import { createServer } from 'node:http';
import { access, mkdir, appendFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const port = Number.parseInt(process.env.DEBUGBUNDLE_MOCK_INGESTION_PORT ?? '18081', 10);
const eventsFile = resolve(process.env.DEBUGBUNDLE_MOCK_EVENTS_FILE ?? '.smoke/ingestion-events.ndjson');
const failFile = process.env.DEBUGBUNDLE_MOCK_FAIL_FILE ? resolve(process.env.DEBUGBUNDLE_MOCK_FAIL_FILE) : null;

async function shouldFail() {
  if (!failFile) {
    return false;
  }

  try {
    await access(failFile);
    return true;
  } catch {
    return false;
  }
}

async function readBody(request) {
  const chunks = [];
  for await (const chunk of request) {
    chunks.push(Buffer.isBuffer(chunk) ? chunk : Buffer.from(chunk));
  }
  return Buffer.concat(chunks).toString('utf8');
}

function sendJson(response, statusCode, body) {
  response.writeHead(statusCode, { 'content-type': 'application/json' });
  response.end(JSON.stringify(body));
}

const server = createServer(async (request, response) => {
  if (request.method === 'GET' && request.url === '/health') {
    sendJson(response, 200, { ok: true });
    return;
  }

  if (request.method === 'GET' && request.url?.startsWith('/v1/sdk/config')) {
    sendJson(response, 404, { error: 'not_configured' });
    return;
  }

  if (request.method !== 'POST' || request.url !== '/v1/events') {
    sendJson(response, 404, { error: 'not_found' });
    return;
  }

  if (await shouldFail()) {
    sendJson(response, 503, { accepted: 0, rejected: 0, errors: ['mock_ingestion_unavailable'] });
    return;
  }

  const bodyText = await readBody(request);
  let payload;
  try {
    payload = JSON.parse(bodyText);
  } catch {
    sendJson(response, 400, { accepted: 0, rejected: 1, errors: ['invalid_json'] });
    return;
  }

  const events = Array.isArray(payload.events) ? payload.events : [];
  await mkdir(dirname(eventsFile), { recursive: true });
  await appendFile(eventsFile, JSON.stringify({
    authorization: request.headers.authorization ?? null,
    events,
  }) + '\n');

  sendJson(response, 202, { accepted: events.length, rejected: 0, errors: [] });
});

server.listen(port, '0.0.0.0');

process.on('SIGTERM', () => {
  server.close(() => process.exit(0));
});