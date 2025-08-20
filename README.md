# HeyHi
LLM Healthboard (Status Monitor)


1) 프로젝트 개요
목적: GPT, Claude, Gemini, Grok 등 주요 LLM API에 대해 **합성 트랜잭션(실제 호출)**으로 가용성/지연/오류를 실시간에 가깝게 측정하고, 대시보드와 알림으로 장애 조기 감지·대응을 가능하게 한다.

구현 방법: 공급사 상태 페이지와 무관하게 실제 호출 결과로 판단

저비용/간단 운영: 무료 티어 위주 인프라 + 초소형 프롬프트로 비용 최소화

즉시 대응: SLO 기반 알림(오류율↑, p95 지연↑, 공식 상태와 불일치 등)

2) 범위(스코프)
공급사별/모델별 합성 체크(초소형 프롬프트, max_tokens=1)

메트릭 수집: ok, http_status, error_type, latency_ms, (가능 시) 토큰 수

대시보드: 성공/오류율, 지연(p50/p95), 이벤트 타임라인

알림: Slack/메일(Webhook)

제외(초기)

실제 트래픽 라우팅/자동 페일오버(후순위)

비용/쿼터 자동 추정의 정밀도 보정(후속)

3) 성공 지표(KPI)

MTTD(Mean Time To Detect) 장애 감지 평균 시간: 5~10분 이내

오탐률(거짓 경보): 주 1회 이하

월간 운영비: 5달러 이하(합성 호출 포함)

가시성: 최근 30일 오류율/지연 트렌드 한 눈에 파악 가능

4) 주요 사용자(Stakeholders)

플랫폼/백엔드 엔지니어: 장애 감지·원인 추정

운영/CS팀: 고객 공지 타이밍 판단

PM/리더: 공급사별 안정성 비교, 의사결정

5) 아키텍처(저비용 버전)

러너/크론: Cloudflare Workers(또는 GitHub Actions/서버 크론)

수집/저장: Supabase(Postgres, 무료 티어)

대시보드: Grafana Cloud Free

알림: Slack Webhook / 이메일

구성

synthetic-checker (2~5분 간격): 실제 API 호출 → 결과 Supabase에 적재

status-poller (5~10분): (선택) 공급사 공식 상태 요약 수집(불일치 탐지용)

Grafana가 Supabase를 읽어 시각화·알림

6) 합성 체크 설계

요청 정책

프롬프트: "ping" 등 1~3토큰, max_tokens=1

타임아웃: 3초(워커 측)

재시도: 없음(샘플링 왜곡 방지), 대신 다음 주기에서 재검증

빈도: 기본 5분(필요 시 2분으로 단축)

지역: (옵션) 서울/도쿄/미국 등 다지역 워커 배치

수집 항목

성공/실패(ok), http_status, error_type(auth/rate_limit/5xx/timeout/dns/other), latency_ms

(가능 시) input_tokens, output_tokens → 월간 비용 추정에 활용

공급사/모델 초기 세트

OpenAI: gpt-4o-mini

Anthropic: claude-3-haiku

Google Gemini: gemini-1.5-flash

(옵션) xAI/Grok, Cohere, Mistral

쿼터/비용 가드

5분 주기 × 35개 모델 = 월 8천1.5만 호출 → **수십 센트몇 달러** 수준

1분 주기로 올려도 월 수 달러 내 관리 가능

7) 데이터 모델(초안)

llm_checks

ts timestamptz

provider text (openai|anthropic|google|…)

model text

region text (옵션)

ok boolean, http_status int, error_type text, latency_ms int

input_tokens int, output_tokens int (옵션)

provider_status_events (선택)

ts, provider, status, title, url

8) 대시보드 설계

상단 요약 타일

공급사별 최근 15분 오류율, p95 지연, 상태 일치 여부(선택)

트렌드 차트

24h/7d latency_ms p50/p95, error_rate

히트맵/테이블

모델×공급사별 오류율

최근 이벤트(상태 변경, 알림 기록)

드릴다운

최근 실패 샘플 20건(HTTP 코드/에러유형/지연)

9) 알림 정책(SLO 기반)

S1(가용성): 5분 창 실패율 > 20% (공급사/모델별)

S2(성능): 10분 창 p95 지연 > 5s

S3(불일치): 공식 상태=Operational인데 실패율 > 10% (status_poller 켠 경우)

S4(지역 이슈): 지역 A만 실패율 급증(다지역일 때)

10) 보안/운영

비밀관리: API Key는 Workers 환경변수/Secrets에 저장

권한 최소화: Supabase는 서비스 롤 키로 쓰기 전용 엔드포인트만 사용

RLS: 대시보드 읽기 계정에 한정, 외부 공개 불가

레이트리밋 대응: 429 감지 시 해당 공급사 체크 간격 2배로 백오프

로깅: Worker 실패 자체도 Slack 알림(잡 자체 문제 가시화)

11) 일정(2주 MVP)

D1~D2: 스키마·레포 구조 세팅, 키 관리

D3~D5: 합성체커(OpenAI/Anthropic/Gemini) 구현·배포(5분 크론)

D6~D7: Supabase 적재 검증, 기본 패널 4개 구성

D8~D9: 알림 룰 2~3개 설정(오류율/p95) + 슬랙 연동

D10~D12: 상태 폴러(선택), 불일치 룰 추가, 다지역(선택)

D13~D14: 문서화/런북/운영점검

12) 리포 구조(예시)
/healthboard
  /checker-workers
    /synthetic-checker
      src/index.ts
      wrangler.toml
    /status-poller (선택)
      src/index.ts
      wrangler.toml
  /infra
    supabase_schema.sql
    grafana_dash.json (대시보드 내보내기)
  /docs
    README.md (키/배포/알림 설정 방법)
    RUNBOOK.md (장애 대응 절차)

13) 런북(요약)

알림 수신 → 대시보드에서 공급사/모델별 오류율·지연 확인

불일치면 공급사 상태 페이지/트위터 확인(내부 링크 모아두기)

원인 유형 분류: 인증/429/5xx/네트워크/시간초과

우리 서비스 영향도 파악 → 임시 공지/우회(수동 전환)

사후 회고: 임계치/빈도 조정, 예외 룰 등록

14) 향후 확장

자동 페일오버 신호: 특정 조건 충족 시 라우터에 “우회 권고” 이벤트 발행

비용/쿼터 그래프: 공급사별 월간 추정 비용 가시화

기능별 분리 체크: Text vs Vision vs Embeddings

시맨틱 품질 샘플링: 주기적으로 짧은 정답 검증(정확도 추세)