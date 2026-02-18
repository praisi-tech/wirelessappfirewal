<div class="modal fade" id="payloadModal" tabindex="-1" aria-labelledby="payloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="payloadModalLabel">
                    <i class="bi bi-shield-text me-2"></i>Security Event Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">Request URL</label>
                    <div id="modalUrl" class="p-2 bg-light border rounded small font-monospace text-break"></div>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold text-muted small text-uppercase">User Agent</label>
                    <div id="modalUA" class="p-2 bg-light border rounded small"></div>
                </div>

                <div class="mb-0">
                    <label class="fw-bold text-muted small text-uppercase">Request Payload / Data</label>
                    <pre id="modalPayload" class="p-3 bg-dark text-success rounded small border" style="max-height: 300px; overflow-y: auto;"></pre>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>