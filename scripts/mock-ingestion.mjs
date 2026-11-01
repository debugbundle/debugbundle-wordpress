import { createServer } from 'node:http';
import { access, mkdir, appendFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const port = Number.parseInt(process.env.DEBUGBUNDLE_MOCK_INGESTION_PORT ?? '18081', 10);
const eventsFile = resolve(process.env.DEBUGBUNDLE_MOCK_EVENTS_FILE ?? '.smoke/ingestion-events.ndjson');
const failFile = process.env.DEBUGBUNDLE_MOCK_FAIL_FILE ? resolve(process.env.DEBUGBUNDLE_MOCK_FAIL_FILE) : null;
const evidenceFile = resolve(dirname(eventsFile), 'evidence-enabled');
const features = ['exception_evidence_v1', 'resource_observations_v1', 'recovery_v1', 'evidence_provenance_v1'];

async function evidenceEnabled() {
  try { await access(evidenceFile); return true; } catch { return false; }
}

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
  const url = new URL(request.url, 'http://mock-ingestion:18081');
  if (request.method === 'GET' && request.url === '/health') {
    sendJson(response, 200, { ok: true });
    return;
  }

  if (request.method === 'GET' && request.url?.startsWith('/v1/sdk/config')) {
    if (request.headers.authorization !== 'Bearer dbundle_proj_smoke') {
      sendJson(response, 401, { error: 'invalid_project_token' });
      return;
    }
    const evidence = await evidenceEnabled();
    sendJson(response, 200, {
      evidence_capabilities: { version: 1, features: evidence ? features : [] },
      probes_enabled: true,
      remote_probes_enabled: false,
      active_probes: [],
      capture_policy: {
        preset: evidence ? 'balanced' : 'minimal',
        capture_logs: 'error',
        capture_request_events: 'failures_only',
        capture_breadcrumbs: 'local_only',
        capture_probe_events: 'buffer_only',
        capture_diagnostic_context: evidence,
      },
    });
    return;
  }

  if (request.method !== 'POST' || url.pathname !== '/v1/events'
      || !['', '?debugbundle_evidence=1'].includes(url.search)) {
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

  sendJson(response, 202, { accepted: events.length, rejected: 0, errors: [],
    evidence_capabilities: { version: 1, features: await evidenceEnabled() ? features : [] } });
});

server.listen(port, '0.0.0.0');

process.on('SIGTERM', () => {
  server.close(() => process.exit(0));
});
