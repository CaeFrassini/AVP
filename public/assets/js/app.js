// URLs da API
const API_BASE = '/api';

// Estado da aplicação
let subjects = [];
let reviews = [];
let performance = [];
let stats = null;

// Gráficos
let subjectChart = null;
let globalChart = null;

// Inicializar aplicação
document.addEventListener('DOMContentLoaded', function() {
    setDefaultDate();
    loadSubjects();
    loadReviews();
    loadPerformance();
    
    // Event listeners
    document.getElementById('addSubjectForm').addEventListener('submit', handleAddSubject);
    document.getElementById('addLessonForm').addEventListener('submit', handleAddLesson);
    document.getElementById('addPerformanceForm').addEventListener('submit', handleAddPerformance);
    
    // Atualizar dados ao trocar de aba
    document.getElementById('revisoes-tab').addEventListener('click', loadReviews);
    document.getElementById('desempenho-tab').addEventListener('click', loadPerformance);
});

// Definir data padrão para hoje
function setDefaultDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('lessonDate').value = today;
    document.getElementById('perfDate').value = today;
}

// ===== DISCIPLINAS =====

async function loadSubjects() {
    try {
        const response = await fetch(`${API_BASE}/subjects.php?action=list`);
        const data = await response.json();
        
        if (data.success === false) {
            showError(data.error);
            return;
        }
        
        subjects = data.data || data;
        renderSubjectsTable();
        updateSubjectSelects();
    } catch (error) {
        console.error('Erro ao carregar disciplinas:', error);
        showError('Erro ao carregar disciplinas');
    }
}

function renderSubjectsTable() {
    const container = document.getElementById('subjectsTable');
    
    if (subjects.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Nenhuma disciplina cadastrada ainda.</p>';
        document.getElementById('subjectsList').classList.remove('active');
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>DISCIPLINA</th>
                        <th class="text-center">TOTAL DE AULAS</th>
                        <th class="text-center">CONCLUÍDAS</th>
                        <th class="text-center">RESTANTES</th>
                        <th class="text-center">% CONCLUÍDO</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    subjects.forEach(subject => {
        const badgeClass = subject.percentage >= 70 ? 'badge-success' : 
                          subject.percentage >= 30 ? 'badge-warning' : 'badge-danger';
        
        html += `
            <tr>
                <td><strong>${escapeHtml(subject.name)}</strong></td>
                <td class="text-center">${subject.total_lessons}</td>
                <td class="text-center">${subject.completed_lessons}</td>
                <td class="text-center">${subject.remaining}</td>
                <td class="text-center">
                    <span class="badge ${badgeClass}">${subject.percentage}%</span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" onclick="deleteSubject(${subject.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
    document.getElementById('subjectsList').classList.remove('active');
}

function updateSubjectSelects() {
    const lessonSelect = document.getElementById('lessonSubject');
    const perfSelect = document.getElementById('perfSubject');
    
    let html = '<option value="">Selecione...</option>';
    subjects.forEach(subject => {
        html += `<option value="${subject.id}">${escapeHtml(subject.name)}</option>`;
    });
    
    lessonSelect.innerHTML = html;
    perfSelect.innerHTML = html;
}

async function handleAddSubject(e) {
    e.preventDefault();
    
    const name = document.getElementById('subjectName').value.trim();
    const total = parseInt(document.getElementById('subjectTotal').value) || 0;
    
    if (!name) {
        showError('Digite o nome da disciplina');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/subjects.php?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                total_lessons: total
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Disciplina adicionada com sucesso!');
            document.getElementById('addSubjectForm').reset();
            loadSubjects();
        } else {
            showError(data.error || 'Erro ao adicionar disciplina');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao adicionar disciplina');
    }
}

async function deleteSubject(id) {
    if (!confirm('Tem certeza que deseja deletar esta disciplina?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/subjects.php?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Disciplina removida!');
            loadSubjects();
        } else {
            showError(data.error || 'Erro ao deletar disciplina');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao deletar disciplina');
    }
}

// ===== AULAS =====

async function handleAddLesson(e) {
    e.preventDefault();
    
    const subjectId = parseInt(document.getElementById('lessonSubject').value);
    const lessonNumber = parseInt(document.getElementById('lessonNumber').value);
    const completedDate = document.getElementById('lessonDate').value;
    
    if (!subjectId || !lessonNumber || !completedDate) {
        showError('Preencha todos os campos');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/lessons.php?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject_id: subjectId,
                lesson_number: lessonNumber,
                completed_date: completedDate
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Aula registrada e revisões criadas!');
            document.getElementById('addLessonForm').reset();
            setDefaultDate();
            loadSubjects();
            loadReviews();
        } else {
            showError(data.error || 'Erro ao registrar aula');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao registrar aula');
    }
}

// ===== REVISÕES =====

async function loadReviews() {
    try {
        const [pending, completed, skipped] = await Promise.all([
            fetch(`${API_BASE}/reviews.php?action=list&status=pending`).then(r => r.json()),
            fetch(`${API_BASE}/reviews.php?action=list&status=completed`).then(r => r.json()),
            fetch(`${API_BASE}/reviews.php?action=list&status=skipped`).then(r => r.json())
        ]);
        
        renderReviews('pending', pending.data || pending);
        renderReviews('completed', completed.data || completed);
        renderReviews('skipped', skipped.data || skipped);
    } catch (error) {
        console.error('Erro ao carregar revisões:', error);
        showError('Erro ao carregar revisões');
    }
}

function renderReviews(status, reviewsList) {
    const container = document.getElementById(`${status}Reviews`);
    
    if (!reviewsList || reviewsList.length === 0) {
        container.innerHTML = '<p class="text-muted">Nenhuma revisão neste status.</p>';
        return;
    }
    
    let html = '';
    reviewsList.forEach(review => {
        const subject = subjects.find(s => s.id === review.subject_id);
        const subjectName = subject ? subject.name : 'Desconhecida';
        const scheduledDate = new Date(review.scheduled_date).toLocaleDateString('pt-BR');
        
        let buttons = '';
        if (status === 'pending') {
            buttons = `
                <button class="btn btn-sm btn-success me-2" onclick="updateReviewStatus(${review.id}, 'completed')">
                    <i class="fas fa-check"></i> Concluir
                </button>
                <button class="btn btn-sm btn-warning" onclick="updateReviewStatus(${review.id}, 'skipped')">
                    <i class="fas fa-times"></i> Pular
                </button>
            `;
        }
        
        html += `
            <div class="review-item ${status}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${escapeHtml(subjectName)} - Aula ${review.lesson_number}</strong>
                        <p class="text-muted mb-0 small">
                            Revisão de ${review.review_type} dia(s) - Agendada para ${scheduledDate}
                        </p>
                    </div>
                    <div>
                        ${buttons}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    document.getElementById(`${status}Reviews`).parentElement.parentElement.querySelector('.loading').classList.remove('active');
}

async function updateReviewStatus(id, status) {
    try {
        const response = await fetch(`${API_BASE}/reviews.php?action=update`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Revisão atualizada!');
            loadReviews();
        } else {
            showError(data.error || 'Erro ao atualizar revisão');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao atualizar revisão');
    }
}

// ===== DESEMPENHO =====

async function loadPerformance() {
    try {
        const response = await fetch(`${API_BASE}/performance.php?action=list`);
        const data = await response.json();
        
        performance = data.data || data;
        renderPerformanceTable();
        
        // Carregar estatísticas
        const statsResponse = await fetch(`${API_BASE}/performance.php?action=stats`);
        const statsData = await statsResponse.json();
        stats = statsData.data || statsData;
        
        renderPerformanceCharts();
    } catch (error) {
        console.error('Erro ao carregar desempenho:', error);
        showError('Erro ao carregar desempenho');
    }
}

function renderPerformanceTable() {
    const container = document.getElementById('performanceList');
    
    if (performance.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Nenhum desempenho registrado ainda.</p>';
        document.getElementById('performanceList').parentElement.parentElement.querySelector('.loading').classList.remove('active');
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>MATÉRIA</th>
                        <th class="text-center">AULA</th>
                        <th class="text-center">TOTAL</th>
                        <th class="text-center">ACERTOS</th>
                        <th class="text-center">% ACERTO</th>
                        <th class="text-center">DATA</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    performance.forEach(perf => {
        const subject = subjects.find(s => s.id === perf.subject_id);
        const subjectName = subject ? subject.name : 'Desconhecida';
        const percentage = Math.round((perf.correct_answers / perf.total_questions) * 100);
        const badgeClass = percentage >= 70 ? 'badge-success' : 
                          percentage >= 50 ? 'badge-warning' : 'badge-danger';
        const date = new Date(perf.registered_date).toLocaleDateString('pt-BR');
        
        html += `
            <tr>
                <td><strong>${escapeHtml(subjectName)}</strong></td>
                <td class="text-center">${perf.lesson_number}</td>
                <td class="text-center">${perf.total_questions}</td>
                <td class="text-center">${perf.correct_answers}</td>
                <td class="text-center">
                    <span class="badge ${badgeClass}">${percentage}%</span>
                </td>
                <td class="text-center">${date}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
    document.getElementById('performanceList').parentElement.parentElement.querySelector('.loading').classList.remove('active');
}

function renderPerformanceCharts() {
    if (!stats) return;
    
    // Gráfico por matéria
    const subjectLabels = stats.by_subject.map(s => s.subject_name);
    const subjectData = stats.by_subject.map(s => s.percentage);
    
    if (subjectChart) {
        subjectChart.destroy();
    }
    
    const subjectCtx = document.getElementById('subjectChart').getContext('2d');
    subjectChart = new Chart(subjectCtx, {
        type: 'doughnut',
        data: {
            labels: subjectLabels.length > 0 ? subjectLabels : ['Sem dados'],
            datasets: [{
                data: subjectData.length > 0 ? subjectData : [100],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Gráfico global
    if (globalChart) {
        globalChart.destroy();
    }
    
    const globalCtx = document.getElementById('globalChart').getContext('2d');
    const globalPercentage = stats.global.percentage;
    const globalColor = globalPercentage >= 70 ? '#10b981' : 
                       globalPercentage >= 50 ? '#f59e0b' : '#ef4444';
    
    globalChart = new Chart(globalCtx, {
        type: 'doughnut',
        data: {
            labels: ['Acertos', 'Erros'],
            datasets: [{
                data: [globalPercentage, 100 - globalPercentage],
                backgroundColor: [globalColor, '#e5e7eb']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
}

async function handleAddPerformance(e) {
    e.preventDefault();
    
    const subjectId = parseInt(document.getElementById('perfSubject').value);
    const lessonNumber = parseInt(document.getElementById('perfLesson').value);
    const totalQuestions = parseInt(document.getElementById('perfTotal').value);
    const correctAnswers = parseInt(document.getElementById('perfCorrect').value);
    const registeredDate = document.getElementById('perfDate').value;
    
    if (!subjectId || !lessonNumber || !totalQuestions || correctAnswers < 0 || !registeredDate) {
        showError('Preencha todos os campos corretamente');
        return;
    }
    
    if (correctAnswers > totalQuestions) {
        showError('Acertos não pode ser maior que o total de questões');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/performance.php?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject_id: subjectId,
                lesson_number: lessonNumber,
                total_questions: totalQuestions,
                correct_answers: correctAnswers,
                registered_date: registeredDate
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Desempenho registrado!');
            document.getElementById('addPerformanceForm').reset();
            setDefaultDate();
            loadPerformance();
        } else {
            showError(data.error || 'Erro ao registrar desempenho');
        }
    } catch (error) {
        console.error('Erro:', error);
        showError('Erro ao registrar desempenho');
    }
}

// ===== UTILITÁRIOS =====

function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container');
    const alertElement = document.createElement('div');
    alertElement.innerHTML = alertHtml;
    container.insertBefore(alertElement.firstElementChild, container.firstChild);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) alert.remove();
    }, 4000);
}

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container');
    const alertElement = document.createElement('div');
    alertElement.innerHTML = alertHtml;
    container.insertBefore(alertElement.firstElementChild, container.firstChild);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert-danger');
        if (alert) alert.remove();
    }, 4000);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
