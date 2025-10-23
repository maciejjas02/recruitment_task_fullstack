// ./assets/js/components/SetupCheck.js
import React, { Component } from 'react';

const Spark = ({ points }) => {
  if (!points || !points.length) return <span>—</span>;
  const w = 120, h = 24, pad = 2;
  const vals = points.map(p => Number(p.mid));
  const min = Math.min(...vals), max = Math.max(...vals), n = vals.length;
  const xs = vals.map((_, i) => (n === 1 ? pad : pad + (i * (w - 2 * pad)) / (n - 1)));
  const ys = vals.map(v => {
    const t = max === min ? 0.5 : (v - min) / (max - min);
    return pad + (h - 2 * pad) * (1 - t);
  });
  const d = xs.map((x, i) => `${i ? 'L' : 'M'}${x},${ys[i]}`).join(' ');
  return <svg width={w} height={h} aria-label="sparkline"><path d={d} fill="none" stroke="black" strokeWidth="2" /></svg>;
};

class SetupCheck extends Component {
  state = {
    loading: true,
    rates: null,
    error: null,
    date: new Date().toISOString().slice(0, 10),
    history: {},
  };

  componentDidMount() { this.loadAll(); }

  loadAll = async () => {
    try {
      this.setState({ loading: true, error: null });
      const { date } = this.state;


      const rates = await fetch('/api/rates', { headers: { Accept: 'application/json' } }).then(r => {
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
      });

      const history = {};
      await Promise.all(
        rates.map(async (r) => {
          const url = `/api/rates/${r.code}/history?date=${encodeURIComponent(date)}`;
          try {
            const data = await fetch(url, { headers: { Accept: 'application/json' } }).then(res => {
              if (!res.ok) throw new Error(`HTTP ${res.status}`);
              return res.json();
            });
            history[r.code] = Array.isArray(data?.points) ? data.points : Array.isArray(data) ? data : [];
          } catch {
            history[r.code] = [];
          }
        })
      );

      this.setState({ rates, history, loading: false });
    } catch (err) {
      this.setState({ error: String(err), loading: false });
    }
  };

  onDateChange = (e) => { this.setState({ date: e.target.value }, this.loadAll); };
  fmt = (v) => (v === null || v === undefined ? '—' : Number(v).toFixed(4));

  render() {
    const { loading, rates, error, date, history } = this.state;

    return (
      <div className="container mt-4">
        <h1>React Setup Check + podgląd kursów</h1>

        <div style={{ margin: '12px 0' }}>
          <label>Data:&nbsp;<input type="date" value={date} onChange={this.onDateChange} /></label>
        </div>

        {error && (
          <div style={{ padding: 12, background: '#300', color: '#f88', borderRadius: 8, marginBottom: 12 }}>
            Błąd API: {error}
          </div>
        )}

        <div style={{ overflowX: 'auto' }}>
          <table className="table" style={{ minWidth: 760, borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ background: '#ececec' }}>
                <th style={{ textAlign: 'left', padding: '8px' }}>Kod</th>
                <th style={{ textAlign: 'right', padding: '8px' }}>Mid</th>
                <th style={{ textAlign: 'right', padding: '8px' }}>Buy</th>
                <th style={{ textAlign: 'right', padding: '8px' }}>Sell</th>
                <th style={{ textAlign: 'left', padding: '8px' }}>Data</th>
                <th style={{ textAlign: 'left', padding: '8px' }}>Wykres (14 dni)</th>
              </tr>
            </thead>
            <tbody>
              {loading && (<tr><td colSpan={6} style={{ padding: 12 }}>Ładowanie…</td></tr>)}

              {!loading && rates && rates.map((row) => {
                const isMajor = row.code === 'EUR' || row.code === 'USD';
                const hist = history[row.code] || [];
                const last = hist.length ? hist[hist.length - 1] : null;
                const show = last ?? row;

                return (
                  <tr
                    key={row.code}
                    style={{ background: isMajor ? '#f7faff' : 'transparent', borderTop: '1px solid #ddd' }}
                    title={last ? `Z historii na ${show.date}` : 'Bieżący kurs (dzisiaj)'}
                  >
                    <td style={{ padding: '8px' }}>{row.code}</td>
                    <td style={{ textAlign: 'right', padding: '8px', fontVariantNumeric: 'tabular-nums' }}>{this.fmt(show.mid)}</td>
                    <td style={{ textAlign: 'right', padding: '8px', fontVariantNumeric: 'tabular-nums' }}>
                      {show.buy === null || show.buy === undefined ? '—' : this.fmt(show.buy)}
                    </td>
                    <td style={{ textAlign: 'right', padding: '8px', fontVariantNumeric: 'tabular-nums' }}>{this.fmt(show.sell)}</td>
                    <td style={{ padding: '8px' }}>{show.date || '—'}</td>
                    <td style={{ padding: '8px' }}><Spark points={hist} /></td>
                  </tr>
                );
              })}

              {!loading && (!rates || !rates.length) && (
                <tr><td colSpan={6} style={{ padding: 12 }}>Brak danych.</td></tr>
              )}
            </tbody>
          </table>
        </div>

        <div style={{ marginTop: 8, color: '#667' }}>
          API: <code>/api/rates</code> | <code>/api/rates/&lt;CODE&gt;/history?date=YYYY-MM-DD</code>
        </div>
      </div>
    );
  }
}

export default SetupCheck;
