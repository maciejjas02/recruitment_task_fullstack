# 💰 Kantor Pro - Currency Exchange System

Profesjonalny system kantorowy z React frontend i Symfony API backend.

## 🚀 Quick Start

### 1. Uruchom Docker
```bash
docker-compose up -d
```

### 2. Otwórz aplikację
Przejdź do: **http://localhost** (automatyczne przekierowanie na app.html)

Alternatywnie: **http://localhost/app.html**

## 📋 Funkcjonalności

### ✅ **System kursów walut**
- 🏦 **NBP API Integration** - live data z Narodowego Banku Polskiego
- 💰 **Business Logic Margins**:
  - EUR/USD: kupno (mid-0.15), sprzedaż (mid+0.11)
  - CZK/IDR/BRL: tylko sprzedaż (mid+0.20)
- 📅 **Historical Data** - kursy z dowolnej daty
- 🔄 **Real-time Updates** - auto-refresh co minutę

### ✅ **Interaktywne wykresy**
- 📊 **Chart.js 4** - profesjonalne wykresy liniowe  
- 🎨 **Triple Line Charts** - kupno (zielona), NBP (niebieska), sprzedaż (czerwona)
- 📈 **Flexible Periods** - 7/14/30 dni lub custom range
- 🎯 **Rich Tooltips** - szczegółowe dane on hover
- 👁️ **Toggle Visibility** - show/hide charts

### ✅ **Obsługiwane waluty**
- 🇪🇺 **EUR** - Euro (buy + sell)
- 🇺🇸 **USD** - US Dollar (buy + sell)  
- 🇨🇿 **CZK** - Czech Koruna (sell only)
- 🇮🇩 **IDR** - Indonesian Rupiah (sell only)
- 🇧🇷 **BRL** - Brazilian Real (sell only)

## 🏗️ Architektura

### **Backend: Symfony 6 + PHP 8.2**
- 🔌 **REST API Endpoints**: 
  - `GET /api/rates` - current rates (all currencies)
  - `GET /api/rates/{code}/history` - historical data
- 🏦 **NBP Client**: PSR-6 cached integration z api.nbp.pl
- 🧮 **Rate Calculator**: Business logic z różnymi marżami
- 🐳 **Docker Container**: recruitment-webserver:80
- ✅ **56 Tests**: Unit + Integration coverage

### **Frontend: Hybrid React + Vanilla JS**
- ⚛️ **React 18 CDN**: State management, UI rendering, cards
- 📊 **Vanilla JavaScript**: Chart.js functions (DOM compatibility)
- 🎨 **External CSS**: Separated styles.css (350+ lines)
- 📱 **Responsive Design**: Mobile-first approach
- 🎭 **Animation Controls**: Pause/resume background animations

### **Key Technical Decisions**
- � **Hybrid Architecture**: React state + vanilla Charts.js
- 🌐 **CDN Dependencies**: No npm build, browser-ready
- 💾 **Smart Caching**: NBP API responses cached
- 🎯 **Clean Separation**: HTML/CSS/JS w logicznych blokach

## 🧪 Testowanie

### **Backend Tests (56 total)**
```bash
# W kontenerze Docker
docker exec -it recruitment-webserver ./vendor/bin/phpunit

# Lub lokalnie
./vendor/bin/phpunit
```

**Test Coverage:**
- ✅ Unit: RateCalculator business logic
- ✅ Integration: API endpoints responses 
- ✅ Service: NbpClient with mocks
- ✅ Validation: Error handling

### **Manual API Testing**
```bash
# Wszystkie aktualne kursy
curl http://localhost/api/rates

# Kursy z konkretnej daty
curl "http://localhost/api/rates?date=2025-10-25"

# Historia EUR ostatnie 7 dni
curl "http://localhost/api/rates/EUR/history?startDate=2025-10-19&endDate=2025-10-26"
```

## 🌐 Dostęp

- **🖥️ Główna aplikacja**: http://localhost (auto-redirect)
- **📱 Direct access**: http://localhost/app.html
- **🔌 API endpoint**: http://localhost/api/rates
- **📊 API history**: http://localhost/api/rates/EUR/history

## 📁 Struktura projektu

```
recruitment_task_fullstack/
├── 🐳 docker-compose.yml          # Docker orchestration
├── 🔧 Dockerfile                  # PHP 8.2 + Apache container
├── 📋 composer.json               # Symfony dependencies
│
├── 🏗️ src/
│   ├── Controller/
│   │   └── RatesController.php    # REST API (homepage + endpoints)
│   └── Service/
│       ├── NbpClient.php          # NBP API client + PSR-6 cache
│       ├── RateCalculator.php     # Business logic margins
│       └── RatesService.php       # Orchestration layer
│
├── 🌐 public/
│   ├── index.php                  # Symfony entry point
│   ├── app.html                   # React hybrid application (570 lines)
│   └── styles.css                 # External CSS (350+ lines)
│
├── ⚙️ config/                     # Symfony configuration
│   ├── routes.yaml                # URL routing
│   └── services.yaml              # DI container
├── 🧪 tests/                      # 56 Unit/Integration tests
└── 📚 templates/                  # Twig templates (unused)
```

## 🎯 API Reference

### **GET /api/rates**
Zwraca aktualne kursy wszystkich obsługiwanych walut.

**Query Parameters:**
- `date` (optional) - format YYYY-MM-DD dla danych historycznych

**Response Example:**
```json
[
  {
    "code": "EUR",
    "mid": 4.2353,
    "buy": 4.0853,      // mid - 0.15 (tylko EUR/USD)
    "sell": 4.3453      // mid + 0.11 (EUR/USD) lub mid + 0.20 (inne)
  },
  {
    "code": "USD", 
    "mid": 3.9876,
    "buy": 3.8376,      // mid - 0.15
    "sell": 4.0976      // mid + 0.11
  },
  {
    "code": "CZK",
    "mid": 0.1756,
    "buy": null,        // brak kupna dla CZK/IDR/BRL
    "sell": 0.1956      // mid + 0.20
  }
]
```

### **GET /api/rates/{code}/history**
Zwraca dane historyczne dla wybranej waluty.

**Path Parameters:**
- `code` - kod waluty (EUR, USD, CZK, IDR, BRL)

**Query Parameters:**
- `startDate` - data początkowa (YYYY-MM-DD)
- `endDate` - data końcowa (YYYY-MM-DD)

**Response Example:**
```json
{
  "code": "EUR",
  "points": [
    {
      "date": "2025-10-26",
      "mid": 4.2353,
      "buy": 4.0853,
      "sell": 4.3453
    },
    {
      "date": "2025-10-25", 
      "mid": 4.2145,
      "buy": 4.0645,
      "sell": 4.3245
    }
  ]
}
```

## 🛠️ Tech Stack

**Backend:**
- 🐘 **PHP 8.2** + 🎵 **Symfony 6.4**
- 🐳 **Docker** + **Apache 2.4**
- 🏦 **NBP API** integration
- 💾 **PSR-6 Cache** interface
- ✅ **PHPUnit** testing framework

**Frontend:**
- ⚛️ **React 18** (CDN, nie npm build)
- 📊 **Chart.js 4** (vanilla JS integration)
- 🎨 **CSS3** z animations i glassmorphism
- � **Responsive design** (CSS Grid + Flexbox)
- 🛠️ **Babel Standalone** (browser JSX transpilation)

**Infrastructure:**
- � **Docker Compose** orchestration
- � **Apache vHost** configuration
- 🔄 **Auto-redirect** (/ → /app.html)
- 📈 **HTTP caching** headers

## 🎨 UI/UX Features

- 🌈 **Animated Gradients** - flowing background colors
- 🎭 **Glassmorphism** - transparent panels with backdrop-blur
- 💫 **Floating Orbs** - subtle animated background elements
- 📱 **Mobile Responsive** - adaptive grid layout
- 🎯 **Interactive Charts** - hover effects, rich tooltips
- ⏸️ **Animation Controls** - pause/resume for accessibility
- 🎪 **Success Status** - auto-hiding notifications z fade-out
- 👁️ **Chart Toggle** - show/hide charts functionality

---

**💡 Production Ready**: Aplikacja gotowa do deployment z full Docker setup

## 🚀 Development Notes

### Quick Docker Setup
```bash
# Start aplikacji
docker-compose up -d

# Stop aplikacji  
docker-compose down

# View logs
docker logs recruitment-webserver

# Enter container
docker exec -it recruitment-webserver bash
```

### Performance Optimizations
- ✅ NBP API responses cached (PSR-6)
- ✅ HTTP cache headers (60s)
- ✅ External CSS separation
- ✅ CDN dependencies (no build step)
- ✅ Optimized Docker image

### Browser Support
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+  
- ✅ Safari 14+
- ✅ Mobile browsers

---

**Autor:** Maciej Jastrzębski  
**Repo:** maciejjas02/recruitment_task_fullstack  
**Branch:** feature/zadanie-Maciej_Jas

