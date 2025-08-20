# HeyHi
LLM Healthboard (Status Monitor)

> **왜 필요한가**
> OpenAI, Claude, Gemini, Grok 등 다양한 LLM을 실제 서비스에서 쓰다 보면, 특정 시간대/지역에서 느려지거나 오류가 발생하는 경우가 많음. 하지만 공급사의 공식 상태 페이지는 지연되거나 불완전하게 업데이트되는 경우가 있음.  
>
> **“문제가 우리 서비스 문제인지, 외부 LLM 문제인지”를 신속히 판단**해야 함. 이를 위해 **실제 호출 결과 기반의 공통 대시보드**가 필요.

## 1. 프로젝트 설명

- GPT, Claude, Gemini, Grok 등 주요 LLM API를 실제 호출(합성 트랜잭션) 으로 모니터링
- 가용성·지연·오류를 실시간에 가깝게 측정 → 대시보드와 알림으로 장애 조기 감지
- 저비용: 무료 티어 인프라 + 초소형 프롬프트로 월 5달러 이하
- 즉시 대응: 오류율·지연·공식 상태 불일치 감지 시 알림

## 2. 범위

- 공급사/모델별 체크 (max_tokens=1)
- 수집: ok, http_status, error_type, latency_ms, (옵션) 토큰 수
- 대시보드: 성공/실패율, p50/p95 지연, 이벤트 타임라인
- 알림: Slack/메일(Webhook)
- 제외: 자동 페일오버, 비용 추정 정밀화(후순위)

## 3. KPI

- MTTD: 5~10분 이내
- 오탐률: 주 1회 이하
- 비용: 월 5달러 이하
- 가시성: 최근 30일 추세 확인 가능

## 4. 아키텍처

- 체커: Cloudflare Workers / GitHub Actions (크론 2~5분)
- DB: Supabase (Postgres Free)
- 대시보드: Grafana Cloud Free
- 알림: Slack Webhook / 이메일

## 5. 합성 체크 설계

- 요청: "ping", max_tokens=1, 타임아웃 3s, 재시도 없음
- 주기: 5분(필요 시 2분까지 단축), 다지역 실행 가능
- 수집 항목: ok, http_status, error_type, latency_ms, (토큰 수)
- 초기 대상: OpenAI(gpt-4o-mini), Anthropic(claude-3-haiku), Google(gemini-1.5-flash)
- 비용: 5분 주기 기준 월 수천1.5만 호출 → 수십 센트몇 달러 수준

## 7. 데이터 모델

- llm_checks: ts, provider, model, region, ok, http_status, error_type, latency_ms, (tokens)
- provider_status_events: ts, provider, status, title, url

## 8. 대시보드

- 요약 타일: 최근 15분 오류율, p95 지연
- 트렌드: 24h/7d latency, error_rate
- 히트맵/테이블: 모델 × 공급사 오류율
- 이벤트 타임라인 + 최근 실패 샘플

## 9. 알림 정책

- 5분 기준 실패율 > 20%
- 10분 기준 p95 지연 > 5s
- 공식 상태=Operational인데 실패율 > 10%
- 특정 지역만 오류율 급증 시 감지


###  모니터링 대상: 최신 LLM 모델 (2025.08 기준)

| 공급사            | 최신 모델                                     |
|-----------------|---------------------------------------------|
| **OpenAI**      | GPT-5                                       |
| **Anthropic**   | Claude Opus 4.1                             |
| **Google**      | Gemini 2.5 Pro                              |
| **xAI (Grok)**  | Grok 4 (+ Grok 4 Heavy)                     |
| **Cohere**      | Command A (03-2025)                         |
| **Mistral**     | Mistral Small 3.1, Magistral (Small/Medium) |
