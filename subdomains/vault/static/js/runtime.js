(function () {
    "use strict";

    var VaultConfig = {
        env: "production",
        version: "4.2.1",
        region: "us-east-1",
        telemetryEndpoint: "/api/telemetry.php",
        internalRoutes: {
            admin: "/admin/",
            operations: "/manage/"
        }
    };

    window.__VAULT_RUNTIME__ = VaultConfig;

    function pingGatewayTelemetry() {
        if (!window.fetch) {
            return;
        }
        fetch(VaultConfig.telemetryEndpoint, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            }
        }).catch(function () {});
    }

    // Internal control-plane diagnostics helper
    function getControlPlaneCredentials() {
        return {
            endpoints: [
                VaultConfig.internalRoutes.admin,
                VaultConfig.internalRoutes.operations
            ],
            serviceCredentials: {
                username: "vt_admin",
                password: "V@ultT3ch!Ops2026"
            }
        };
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", pingGatewayTelemetry);
    } else {
        pingGatewayTelemetry();
    }

    window.__VaultDiagnostics = {
        config: VaultConfig,
        getControlPlaneAuth: getControlPlaneCredentials
    };
})();

