<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>League Demo</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 1rem; }
        .container { display: flex; gap: 1rem; }
        .panel {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 1rem;
        }
        .panel h2 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: .5rem; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        button { padding: 6px 12px; }
        .footer { text-align: center; }
        button:disabled { opacity: .5; cursor: not-allowed; }
    </style>
</head>
<body>
<div id="app">
    <h1>Insider Champions League Demo</h1>
    <div class="container">
        <div class="panel">
            <h2>League Table</h2>
            <table>
                <thead>
                <tr><th>Team</th><th>Pts</th><th>GD</th></tr>
                </thead>
                <tbody>
                <tr v-for="row in standings" :key="row.team_id">
                    <td>@{{ row.team_name }}</td>
                    <td>@{{ row.points }}</td>
                    <td>@{{ row.gd }}</td>
                </tr>
                </tbody>
            </table>
            <div class="footer">
                <button @click="playAll">Play All</button>
            </div>
        </div>

        <div class="panel">
            <h2>Match Results (Week @{{ currentWeek + 1 }})</h2>
            <table>
                <thead>
                <tr>
                    <th>Wk</th><th>Home</th><th>Score</th><th>Away</th><th>Score</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="m in fixtures" :key="m.id" v-if="m.week === currentWeek + 1">
                    <td>@{{ m.week }}</td>
                    <td>@{{ m.home_team.name }}</td>
                    <td>@{{ m.home_score == null ? '-' : m.home_score }}</td>
                    <td>@{{ m.away_team.name }}</td>
                    <td>@{{ m.away_score == null ? '-' : m.away_score }}</td>
                </tr>
                </tbody>
            </table>
            <div class="footer">
                <button
                    @click="playNext"
                    :disabled="!canNext"
                >Next Week</button>
            </div>
        </div>

        <div class="panel">
            <h2>Predictions</h2>
            <table>
                <thead>
                <tr><th>Team</th><th>Win %</th></tr>
                </thead>
                <tbody>
                <tr v-for="p in predictions" :key="p.team_id">
                    <td>@{{ getTeamName(p.team_id) }}</td>
                    <td>@{{ p.probability }}%</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    new Vue({
        el: '#app',
        data: {
            standings: [],
            fixtures: [],
            predictions: [],
            teamsMap: {},
        },
        computed: {
            currentWeek() {
                const played = this.fixtures.filter(m => m.home_score !== null);
                return played.length
                    ? Math.max(...played.map(m => m.week))
                    : 0;
            },
            canNext() {
                return this.fixtures.some(m => m.week === this.currentWeek + 1 && m.home_score === null);
            }
        },
        mounted() {
            this.refreshAll();
        },
        methods: {
            async refreshAll() {
                const [stRes, fxRes, prRes] = await Promise.all([
                    axios.get('/api/league/standings'),
                    axios.get('/api/league/fixtures'),
                    axios.get('/api/league/predictions'),
                ]);
                this.standings   = stRes.data.data;
                this.fixtures    = fxRes.data.data;
                this.predictions = prRes.data.data;
                this.fixtures.forEach(m => {
                    this.teamsMap[m.home_team.id] = m.home_team.name;
                    this.teamsMap[m.away_team.id] = m.away_team.name;
                });
            },
            async playNext() {
                if (!this.canNext) return;
                await axios.post('/api/league/next-week');
                await this.refreshAll();
            },
            async playAll() {
                await axios.post('/api/league/play-all');
                await this.refreshAll();
            },
            getTeamName(id) {
                return this.teamsMap[id] || '—';
            }
        }
    });
</script>
</body>
</html>
