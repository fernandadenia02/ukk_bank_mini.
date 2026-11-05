#!/usr/bin/env python3

import os
import ssl
import sys
from pathlib import PurePosixPath
from zipfile import ZipFile
from ftplib import FTP_TLS, error_perm


def required_env(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise RuntimeError(f"Required GitHub secret or variable is missing: {name}")
    return value


def ensure_remote_directory(ftp: FTP_TLS, directory: str) -> None:
    if ".." in PurePosixPath(directory).parts:
        raise ValueError(f"Remote directory must not contain '..': {directory}")

    if directory.startswith("/"):
        ftp.cwd("/")

    for part in PurePosixPath(directory).parts:
        if part in ("", "/", "."):
            continue
        try:
            ftp.cwd(part)
        except error_perm:
            ftp.mkd(part)
            ftp.cwd(part)


def upload_archive(ftp: FTP_TLS, archive_path: str, remote_directory: str) -> int:
    starting_directory = ftp.pwd()
    ensure_remote_directory(ftp, remote_directory)
    uploaded_files = 0

    with ZipFile(archive_path) as archive:
        for entry in archive.infolist():
            path = PurePosixPath(entry.filename)
            if path.is_absolute() or ".." in path.parts or "\\" in entry.filename:
                raise ValueError(f"Unsafe deployment archive path: {entry.filename}")

            if entry.is_dir():
                ftp.cwd(starting_directory)
                ensure_remote_directory(ftp, remote_directory)
                ensure_remote_directory(ftp, entry.filename)
                continue

            parent = path.parent.as_posix()
            ftp.cwd(starting_directory)
            ensure_remote_directory(ftp, remote_directory)
            if parent != ".":
                ensure_remote_directory(ftp, parent)

            with archive.open(entry) as source:
                ftp.storbinary(f"STOR {path.name}", source)
            uploaded_files += 1
            print(f"Uploaded {entry.filename} to {remote_directory}")
            ftp.cwd(starting_directory)

    return uploaded_files


def main() -> None:
    if len(sys.argv) != 3:
        raise SystemExit(
            "Usage: deploy-ftp.py LARAVEL_APP_ZIP PUBLIC_HTML_ZIP"
        )

    host = required_env("FTP_HOST")
    username = required_env("FTP_USERNAME")
    password = required_env("FTP_PASSWORD")
    app_directory = required_env("FTP_APP_DIR")
    public_directory = required_env("FTP_PUBLIC_DIR")
    port = int(os.environ.get("FTP_PORT") or "21")

    ftp = FTP_TLS(context=ssl.create_default_context())
    ftp.connect(host, port, timeout=30)
    try:
        ftp.login(username, password)
        ftp.prot_p()
        ftp.set_pasv(True)

        app_count = upload_archive(ftp, sys.argv[1], app_directory)
        public_count = upload_archive(ftp, sys.argv[2], public_directory)
        ftp.quit()
    except Exception:
        ftp.close()
        raise

    print(
        f"FTPS deployment completed: {app_count} application files and "
        f"{public_count} public files uploaded."
    )


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        print(f"FTPS deployment failed: {error}", file=sys.stderr)
        raise SystemExit(1) from error
