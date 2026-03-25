FROM python:3.11-slim

WORKDIR /app

COPY api/requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY api .

COPY ai/model_checkpoint.pt .

CMD sh -c "uvicorn app:app --host 0.0.0.0 --port \"
