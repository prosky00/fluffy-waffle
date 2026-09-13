#!/usr/bin/env python3
"""Minimal GitHub webhook receiver: on a verified push to the configured
branch, runs `git pull` + `docker compose up -d --build` in the repo dir.
No framework, no deps beyond the standard library — this is a small,
single-purpose listener, not a general-purpose app server."""

import hashlib
import hmac
import json
import os
import subprocess
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

SECRET = os.environ["WEBHOOK_SECRET"].encode()
REPO_DIR = os.environ.get("REPO_DIR", "/opt/faction")
BRANCH = os.environ.get("DEPLOY_BRANCH", "test")
PORT = int(os.environ.get("WEBHOOK_PORT", "9001"))


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length)

        signature = self.headers.get("X-Hub-Signature-256", "")
        expected = "sha256=" + hmac.new(SECRET, body, hashlib.sha256).hexdigest()
        if not hmac.compare_digest(signature, expected):
            self.send_response(401)
            self.end_headers()
            self.wfile.write(b"bad signature")
            return

        try:
            payload = json.loads(body)
        except ValueError:
            self.send_response(400)
            self.end_headers()
            return

        ref = payload.get("ref", "")
        if ref != f"refs/heads/{BRANCH}":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(f"ignored ref {ref}".encode())
            return

        self.send_response(202)
        self.end_headers()
        self.wfile.write(b"deploying")
        self.deploy()

    def deploy(self):
        print(f"==> Deploying: git pull + docker compose up -d --build in {REPO_DIR}")
        subprocess.run(["git", "pull", "origin", BRANCH], cwd=REPO_DIR, check=False)
        subprocess.run(
            ["docker", "compose", "up", "-d", "--build"], cwd=REPO_DIR, check=False
        )
        print("==> Deploy finished")

    def log_message(self, fmt, *args):
        print(f"{self.address_string()} - {fmt % args}")


if __name__ == "__main__":
    server = ThreadingHTTPServer(("0.0.0.0", PORT), Handler)
    print(f"==> Webhook listener on :{PORT}, deploying branch '{BRANCH}' from {REPO_DIR}")
    server.serve_forever()
