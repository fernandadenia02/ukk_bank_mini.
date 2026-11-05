#!/usr/bin/env python3

import sys
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile


ROOT = Path(__file__).resolve().parents[2]
APP_PATHS = (
    "app",
    "bootstrap",
    "config",
    "database",
    "lang",
    "resources",
    "routes",
    "artisan",
    "composer.json",
    "composer.lock",
)


def is_environment_or_log(path: Path) -> bool:
    name = path.name.lower()
    return name == ".env" or name.startswith(".env.") or name.endswith(".log") or ".log." in name


def app_files():
    for entry in APP_PATHS:
        source = ROOT / entry
        if not source.exists():
            if entry == "lang":
                continue
            raise FileNotFoundError(f"Required application path is missing: {entry}")

        paths = (source,) if source.is_file() else source.rglob("*")
        for path in paths:
            if not path.is_file() or path.is_symlink():
                continue

            relative = path.relative_to(ROOT)
            parts = relative.parts
            if is_environment_or_log(path):
                continue
            if parts[0] == "bootstrap" and parts[1:2] == ("cache",):
                if parts[2:] != (".gitignore",):
                    continue
            if parts[0] == "database" and path.suffix.lower() in {".sqlite", ".sqlite3", ".db"}:
                continue

            yield path, relative.as_posix()


def public_files():
    public = ROOT / "public"
    if not public.is_dir():
        raise FileNotFoundError("Required public directory is missing")

    for path in public.rglob("*"):
        if not path.is_file() or path.is_symlink() or is_environment_or_log(path):
            continue
        if any(part == ".git" or part.startswith(".git") for part in path.relative_to(public).parts):
            continue
        yield path, path.relative_to(public).as_posix()


def write_zip(destination: Path, files) -> None:
    with ZipFile(destination, "w", compression=ZIP_DEFLATED) as archive:
        for path, archive_name in files:
            archive.write(path, archive_name)


def main() -> None:
    if len(sys.argv) != 2:
        raise SystemExit("Usage: package-deployment.py OUTPUT_DIRECTORY")

    output = Path(sys.argv[1]).resolve()
    output.mkdir(parents=True, exist_ok=True)

    if not (ROOT / "vendor" / "autoload.php").is_file():
        raise FileNotFoundError("vendor/autoload.php is missing")
    if not (ROOT / "public" / "build" / "manifest.json").is_file():
        raise FileNotFoundError("public/build/manifest.json is missing; run npm run build first")

    write_zip(output / "laravel_app.zip", app_files())
    write_zip(output / "public_html.zip", public_files())


if __name__ == "__main__":
    main()
