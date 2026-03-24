FROM python:3.11-slim

WORKDIR /app

# Install API requirements
COPY api/requirements.txt /app/api/requirements.txt
RUN pip install --no-cache-dir -r /app/api/requirements.txt

# Create cache directory for model checkpoint
RUN mkdir -p /tmp && chmod 777 /tmp

COPY api /app/api
COPY ai /app/ai

ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD python -c "import requests; requests.get('http://localhost:${PORT:-10000}/docs', timeout=5)" || exit 1

CMD ["sh", "-c", "uvicorn api.app:app --host 0.0.0.0 --port ${PORT:-10000}"]
