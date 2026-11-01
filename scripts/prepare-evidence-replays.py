"""Create independent retry cases from the actual packaged browser's events."""
import copy
import json
from pathlib import Path
import sys
import uuid

root = Path(sys.argv[1])
original = json.loads((root / 'browser-evidence.json').read_text())
for kind in ('spooled', 'revoked'):
    value = copy.deepcopy(original)
    failure_ref = str(uuid.uuid4())
    page_id = str(uuid.uuid4())
    for event in value['batch']:
        event['event_id'] = str(uuid.uuid4())
        if event['event_type'] == 'frontend_exception':
            reference = event['context']['evidence_reference']
            event['payload']['message'] = kind + ' ZIP failure'
        else:
            reference = event['payload']
            event['payload']['attempt_id'] = str(uuid.uuid4())
        reference['failure_ref'] = failure_ref
        reference['scope']['page_id'] = page_id
    (root / (kind + '-evidence.json')).write_text(json.dumps(value))
