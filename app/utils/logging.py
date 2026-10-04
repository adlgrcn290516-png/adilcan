"""Merkezi log kurulumu. Secret'ları maskeler."""
import logging
import re
import sys

_SECRET_RE = re.compile(r"(signature=)[0-9a-fA-F]+|(X-MBX-APIKEY['\": ]+)[A-Za-z0-9]+")


class RedactFilter(logging.Filter):
    def filter(self, record: logging.LogRecord) -> bool:
        msg = record.getMessage()
        record.msg = _SECRET_RE.sub(lambda m: (m.group(1) or m.group(2)) + "***", msg)
        record.args = ()
        return True


def setup_logging(level: str = "INFO") -> None:
    h = logging.StreamHandler(sys.stdout)
    h.setFormatter(logging.Formatter("%(asctime)s %(levelname)-7s %(name)s | %(message)s"))
    h.addFilter(RedactFilter())
    root = logging.getLogger()
    root.handlers[:] = [h]
    root.setLevel(level)
