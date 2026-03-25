FROM python:3.11-slim

WORKDIR /app

# Install dependencies
COPY . .

RUN pip install --no-cache-dir -r api/requirements.txt

# Create temp dir for model cache
RUN mkdir -p /tmp && chmod 777 /tmp

ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

CMD ["sh", "-c", "uvicorn api.app:app --host 0.0.0.0 --port ${PORT:-8000}"]