<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIFA World Cup 2026 - Live Stats & Schedule</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wc: {
                            dark: '#0f0a0c', // Dark background with slight red tint
                            card: '#1a1315',
                            border: '#2a1b20',
                            accent: '#d94825', // World cup red/orange
                            text: '#f2f2f2'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', "Liberation Mono", "Courier New", 'monospace']
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background-color: #0f0a0c;
            color: #f2f2f2;
            background-image: radial-gradient(circle at top right, rgba(217, 72, 37, 0.1) 0%, transparent 40%),
                              radial-gradient(circle at bottom left, rgba(217, 72, 37, 0.05) 0%, transparent 40%);
            min-height: 100vh;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f0a0c; }
        ::-webkit-scrollbar-thumb { background: #2a1b20; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #d94825; }
        
        .loader {
            border: 3px solid rgba(255,255,255,0.1);
            border-top: 3px solid #d94825;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="font-sans antialiased selection:bg-wc-accent selection:text-white pb-20">
    
    <!-- Navbar -->
    <nav class="border-b border-wc-border bg-wc-dark/90 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="<?php echo SITE_URL; ?>" class="flex items-center space-x-2 text-gray-300 hover:text-white transition">
                <i class="fas fa-arrow-left"></i>
                <span class="font-semibold">আলোকপাত</span>
            </a>
            
            <div class="flex items-center space-x-2">
                <span class="text-xl font-bold tracking-wider">World Cup 2026</span>
                <i class="fas fa-trophy text-wc-accent"></i>
            </div>
            
            <div class="flex space-x-4 text-sm font-medium">
                <a href="#teams" class="hidden md:block hover:text-wc-accent transition">Teams</a>
                <a href="#groups" class="hidden md:block hover:text-wc-accent transition">Groups</a>
                <a href="#games" class="hidden md:block hover:text-wc-accent transition">Games</a>
            </div>
        </div>
    </nav>
    
    <!-- Hero / Timer Section -->
    <section class="max-w-4xl mx-auto px-4 pt-16 pb-12 text-center">
        <div class="inline-block px-4 py-1 rounded-full border border-wc-accent/30 bg-wc-accent/10 text-wc-accent text-sm mb-6">
            June 11 to July 19, 2026 <i class="fas fa-trophy ml-1"></i>
        </div>
        
        <h1 class="text-4xl md:text-6xl font-black mb-4 bg-clip-text text-transparent bg-gradient-to-r from-gray-100 to-gray-500">
            FIFA World Cup 2026
        </h1>
        
        <p class="text-gray-400 max-w-2xl mx-auto mb-12">
            The world's largest sporting event with 48 teams from around the world in the United States, Canada and Mexico
        </p>
        
        <!-- Timer Cards -->
        <div class="flex justify-center gap-4 md:gap-6 mb-16" id="main-timer">
            <!-- Populated by JS -->
        </div>
        
        <!-- Stats Row -->
        <div class="flex justify-center gap-8 md:gap-16">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-amber-500 mb-1">16</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Stadiums</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-amber-500 mb-1">104</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Games</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-amber-500 mb-1">12</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Groups</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-amber-500 mb-1">48</div>
                <div class="text-xs text-gray-500 uppercase tracking-widest">Teams</div>
            </div>
        </div>
    </section>
    
    <!-- Games Schedule Section -->
    <section id="games" class="max-w-7xl mx-auto px-4 py-12 border-t border-wc-border">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold mb-2">Games schedule</h2>
            <p class="text-gray-500 text-sm">View the schedule and results of all World Cup matches</p>
        </div>
        
        <div id="games-loading" class="loader"></div>
        <!-- Note: We'll show just the first 16 games to match the UI, as loading all 104 can clutter the page -->
        <div id="games-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Populated by JS -->
        </div>
        <div class="text-center mt-8">
            <button id="load-more-games" class="px-6 py-2 bg-wc-card border border-wc-border hover:border-wc-accent rounded text-sm transition hidden">Load More Games</button>
        </div>
    </section>

    <!-- Participating Teams Section -->
    <section id="teams" class="max-w-7xl mx-auto px-4 py-12 border-t border-wc-border">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold mb-2">Participating teams</h2>
            <p class="text-gray-500 text-sm">The 48 best football teams in the world</p>
        </div>
        
        <div id="teams-loading" class="loader"></div>
        <div id="teams-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Populated by JS -->
        </div>
    </section>
    
    <!-- Group Table Section -->
    <section id="groups" class="max-w-7xl mx-auto px-4 py-12 border-t border-wc-border">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold mb-2">Group table</h2>
            <p class="text-gray-500 text-sm">Ranking of teams in the group stage</p>
        </div>
        
        <div id="groups-loading" class="loader"></div>
        <div id="groups-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Populated by JS -->
        </div>
    </section>
    
    <script>
        // --- TIMER ---
        function updateMainTimer() {
            const target = new Date("June 11, 2026 12:00:00").getTime();
            const now = new Date().getTime();
            const diff = target - now;
            
            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                document.getElementById('main-timer').innerHTML = `
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 md:p-6 min-w-[80px] md:min-w-[100px]">
                        <div class="text-2xl md:text-4xl font-bold text-wc-accent font-mono mb-2">${days.toString().padStart(2, '0')}</div>
                        <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-widest">Days</div>
                    </div>
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 md:p-6 min-w-[80px] md:min-w-[100px]">
                        <div class="text-2xl md:text-4xl font-bold text-wc-accent font-mono mb-2">${hours.toString().padStart(2, '0')}</div>
                        <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-widest">Hours</div>
                    </div>
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 md:p-6 min-w-[80px] md:min-w-[100px]">
                        <div class="text-2xl md:text-4xl font-bold text-wc-accent font-mono mb-2">${minutes.toString().padStart(2, '0')}</div>
                        <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-widest">Minutes</div>
                    </div>
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 md:p-6 min-w-[80px] md:min-w-[100px]">
                        <div class="text-2xl md:text-4xl font-bold text-wc-accent font-mono mb-2">${seconds.toString().padStart(2, '0')}</div>
                        <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-widest">Seconds</div>
                    </div>
                `;
            }
        }
        setInterval(updateMainTimer, 1000);
        updateMainTimer();

        // --- API INTEGRATION ---
        const API_BASE = 'https://worldcup26.ir/get';
        
        let allTeams = [];
        let allGames = [];
        let gamesVisible = 16;
        
        // Helper to get team info by ID
        function getTeam(id) {
            return allTeams.find(t => t.id === id || t.team_id === id || t._id === id) || { name_en: 'Unknown', flag: '' };
        }

        // 1. Fetch Teams
        async function fetchTeams() {
            try {
                const res = await fetch(`${API_BASE}/teams`);
                const data = await res.json();
                allTeams = data.teams || [];
                renderTeams();
                
                // Fetch others only after teams are loaded
                fetchGroups();
                fetchGames();
            } catch (err) {
                document.getElementById('teams-loading').innerHTML = '<span class="text-red-500">Failed to load teams</span>';
            }
        }
        
        // Render Teams
        function renderTeams() {
            document.getElementById('teams-loading').style.display = 'none';
            const grid = document.getElementById('teams-grid');
            
            let html = '';
            allTeams.forEach(team => {
                html += `
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 flex flex-col items-center hover:border-wc-accent transition duration-300">
                        <img src="${team.flag}" alt="${team.name_en}" class="w-12 h-auto rounded shadow-sm mb-3">
                        <h3 class="font-bold text-sm text-center mb-1">${team.name_en}</h3>
                        <div class="flex items-center space-x-2 text-[10px] uppercase">
                            <span class="text-wc-accent">${team.fifa_code}</span>
                            <span class="text-gray-600">Group ${team.groups}</span>
                        </div>
                    </div>
                `;
            });
            grid.innerHTML = html;
        }
        
        // 2. Fetch Groups
        async function fetchGroups() {
            try {
                const res = await fetch(`${API_BASE}/groups`);
                const data = await res.json();
                renderGroups(data.groups || []);
            } catch (err) {
                document.getElementById('groups-loading').innerHTML = '<span class="text-red-500">Failed to load groups</span>';
            }
        }
        
        // Render Groups
        function renderGroups(groups) {
            document.getElementById('groups-loading').style.display = 'none';
            const grid = document.getElementById('groups-grid');
            
            groups.sort((a, b) => a.name.localeCompare(b.name));
            
            let html = '';
            groups.forEach(group => {
                // Sort teams by points (desc)
                const teams = group.teams.sort((a, b) => parseInt(b.pts) - parseInt(a.pts));
                
                let rowsHtml = '';
                teams.forEach((t, index) => {
                    let teamInfo = getTeam(t.team_id);
                    rowsHtml += `
                        <div class="flex items-center justify-between py-2 border-t border-wc-border/50 text-sm">
                            <div class="w-6 text-center text-gray-500 text-xs">${index + 1}</div>
                            <div class="flex items-center space-x-2 flex-1 pl-2">
                                <img src="${teamInfo.flag}" alt="${teamInfo.name_en}" class="w-5 h-auto rounded-sm">
                                <span class="font-medium">${teamInfo.name_en}</span>
                            </div>
                            <div class="w-8 text-center text-gray-400">${t.mp}</div>
                            <div class="w-8 text-center text-gray-400">${t.w}</div>
                            <div class="w-8 text-center text-wc-accent font-bold">${t.pts}</div>
                        </div>
                    `;
                });
                
                html += `
                    <div class="bg-wc-card border border-wc-border rounded-xl overflow-hidden shadow-lg hover:border-wc-accent/50 transition">
                        <div class="bg-gradient-to-r from-amber-600/20 to-wc-accent/20 px-4 py-3 border-b border-wc-border flex items-center justify-between">
                            <i class="fas fa-trophy text-amber-500/70"></i>
                            <span class="font-bold">Group ${group.name}</span>
                        </div>
                        <div class="px-2 py-1">
                            <div class="flex items-center justify-between py-2 text-[10px] font-bold text-gray-500 uppercase">
                                <div class="w-6 text-center">#</div>
                                <div class="flex-1 pl-2">Team</div>
                                <div class="w-8 text-center">MP</div>
                                <div class="w-8 text-center">W</div>
                                <div class="w-8 text-center">Pts</div>
                            </div>
                            ${rowsHtml}
                        </div>
                    </div>
                `;
            });
            grid.innerHTML = html;
        }

        // 3. Fetch Games
        async function fetchGames() {
            try {
                const res = await fetch(`${API_BASE}/games`);
                const data = await res.json();
                allGames = data.games || [];
                renderGames();
            } catch (err) {
                document.getElementById('games-loading').innerHTML = '<span class="text-red-500">Failed to load games</span>';
            }
        }
        
        // Render Games
        function renderGames() {
            document.getElementById('games-loading').style.display = 'none';
            const grid = document.getElementById('games-grid');
            
            const visibleGames = allGames.slice(0, gamesVisible);
            
            let html = '';
            visibleGames.forEach(game => {
                let homeTeam = getTeam(game.home_team_id);
                let awayTeam = getTeam(game.away_team_id);
                
                // API might use '-' or actual scores if match has been played.
                let homeScore = game.home_score !== null ? game.home_score : '-';
                let awayScore = game.away_score !== null ? game.away_score : '-';
                
                // Format date (Game date)
                let dateStr = game.date_time ? new Date(game.date_time).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' }) : 'TBD';
                
                html += `
                    <div class="bg-wc-card border border-wc-border rounded-xl p-4 shadow-lg hover:border-wc-accent/50 transition relative">
                        
                        <div class="flex justify-between items-center text-[10px] uppercase text-gray-500 font-bold mb-4 border-b border-wc-border pb-2">
                            <span>Game ${game.match_number || '-'}</span>
                            <span class="text-amber-500">Group ${game.group_name || 'N/A'}</span>
                        </div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <!-- Home Team -->
                            <div class="flex flex-col items-center w-1/3">
                                <img src="${homeTeam.flag}" alt="" class="w-10 h-auto rounded shadow-sm mb-2">
                                <span class="font-bold text-xs text-center">${homeTeam.name_en}</span>
                            </div>
                            
                            <!-- Score -->
                            <div class="w-1/3 text-center">
                                <div class="text-xs text-gray-500 uppercase mb-1">Opposite</div>
                                <div class="text-2xl font-black text-wc-accent">
                                    ${homeScore} <span class="text-gray-600">-</span> ${awayScore}
                                </div>
                            </div>
                            
                            <!-- Away Team -->
                            <div class="flex flex-col items-center w-1/3">
                                <img src="${awayTeam.flag}" alt="" class="w-10 h-auto rounded shadow-sm mb-2">
                                <span class="font-bold text-xs text-center">${awayTeam.name_en}</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center text-[10px] text-gray-400 mt-2 border-t border-wc-border pt-3">
                            <span class="truncate pr-2 w-1/2" title="${game.stadium_id || 'Stadium TBD'}"><i class="fas fa-map-marker-alt mr-1"></i> TBD</span>
                            <span>${dateStr}</span>
                        </div>
                    </div>
                `;
            });
            
            grid.innerHTML = html;
            
            const btn = document.getElementById('load-more-games');
            if (allGames.length > gamesVisible) {
                btn.classList.remove('hidden');
                btn.onclick = () => {
                    gamesVisible += 16;
                    renderGames();
                };
            } else {
                btn.classList.add('hidden');
            }
        }

        // Initialize fetching pipeline
        document.addEventListener('DOMContentLoaded', () => {
            fetchTeams(); // This will chain fetchGroups and fetchGames
        });
    </script>
</body>
</html>
