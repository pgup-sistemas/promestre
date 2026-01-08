<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Minha Agenda';
require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-calendar-alt me-2"></i> Minha Agenda</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="agenda_cadastro.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Novo Agendamento</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<style>
    /* Ajustes para deixar a agenda mais compacta */
    .fc .fc-toolbar-title {
        font-size: 1.25rem !important;
    }
    .fc .fc-button {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
    .fc .fc-daygrid-day-frame {
        min-height: 80px !important; /* Altura mínima menor para as células */
    }
    .fc .fc-event {
        font-size: 0.75rem !important;
        padding: 1px 2px !important;
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: #e3e6f0;
    }
    /* Altura total do calendário */
    #calendar {
        /* max-height: 75vh; Removido para permitir que o card cresça junto com o calendário nas visualizações de dia/semana */
    }
    
    /* Mobile CSS */
    @media (max-width: 767.98px) {
        .fc .fc-toolbar {
            flex-direction: column;
            gap: 10px;
        }
        .fc .fc-toolbar-title {
            font-size: 1.1rem !important;
        }
        .fc .fc-button {
            padding: 0.35rem 0.6rem !important; /* Touch friendly */
            font-size: 0.8rem !important;
        }
        #calendar {
            max-height: none; /* Deixa crescer no mobile */
        }
        .fc-list-event-title {
            font-weight: 500;
        }
        .fc-list-day-text {
            text-transform: capitalize;
        }
    }
</style>

<!-- Modal Detalhes do Evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventTitle">Detalhes do Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush mb-3">
                    <div class="list-group-item px-0">
                        <div class="text-muted small">Horário</div>
                        <div class="fw-semibold" id="eventTime"></div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="text-muted small">Aluno</div>
                        <div class="fw-semibold" id="eventAluno"></div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="text-muted small">Status</div>
                        <div id="eventStatus"></div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="text-muted small">Presença</div>
                        <div id="eventPresenca"></div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="text-muted small">Observações</div>
                        <div id="eventObs"></div>
                    </div>
                </div>

                <!-- Attendance List Section -->
                <div id="attendanceSection" class="mt-3">
                    <div class="fw-semibold mb-2">Lista de Chamada</div>
                    <div id="attendanceList" class="mb-2">
                        <!-- Loading or list content will go here -->
                        <div class="text-center text-muted py-2">
                            <i class="fas fa-spinner fa-spin"></i> Carregando lista...
                        </div>
                    </div>
                    <button id="btnSaveAttendance" class="btn btn-primary btn-sm w-100 d-none" onclick="salvarChamada()">
                        <i class="fas fa-check me-1"></i> Salvar Chamada
                    </button>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a id="btnWhatsapp" href="#" target="_blank" class="btn btn-success btn-sm d-none">
                        <i class="fab fa-whatsapp me-1"></i> Confirmar no WhatsApp
                    </a>
                    <div class="row g-2">
                        <div class="col-6">
                            <a id="btnEdit" href="#" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        </div>
                        <div class="col-6">
                            <a id="btnDelete" href="#" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Tem certeza que deseja excluir?');">
                                <i class="fas fa-trash me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS/JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var isMobile = window.innerWidth < 768;
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: isMobile ? 'prev,next' : 'prev,next today',
            center: 'title',
            right: isMobile ? 'listWeek,dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        height: 'auto',
        contentHeight: 'auto',
        aspectRatio: isMobile ? 0.8 : 1.35,
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia',
            list: 'Lista'
        },
        windowResize: function(view) {
            if (window.innerWidth < 768) {
                calendar.changeView('listWeek');
                calendar.setOption('headerToolbar', {
                    left: 'prev,next',
                    center: 'title',
                    right: 'listWeek,dayGridMonth'
                });
            } else {
                calendar.changeView('dayGridMonth');
                calendar.setOption('headerToolbar', {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                });
            }
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('api_agenda.php', {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                successCallback(data);
            })
            .catch(error => {
                console.error('Erro ao carregar eventos:', error);
                failureCallback(error);
            });
        },
        eventClick: function(info) {
            var event = info.event;
            var props = event.extendedProps;
            
            // Store current event ID globally for attendance marking
            window.currentEventId = event.id;
            
            // Populate Modal
            document.getElementById('eventTitle').innerText = event.title;
            
            // Format dates
            var start = event.start.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
            var end = event.end ? event.end.toLocaleString('pt-BR', { timeStyle: 'short' }) : '';
            document.getElementById('eventTime').innerText = start + (end ? ' - ' + end : '');
            
            document.getElementById('eventAluno').innerText = props.turma_nome ? 'Turma: ' + props.turma_nome : (props.aluno_nome || 'N/A');

            var statusBadge = '';
            if(props.status === 'agendado') statusBadge = '<span class="badge bg-primary">Agendado</span>';
            else if(props.status === 'realizado') statusBadge = '<span class="badge bg-success">Realizado</span>';
            else if(props.status === 'cancelado') statusBadge = '<span class="badge bg-danger">Cancelado</span>';
            document.getElementById('eventStatus').innerHTML = statusBadge;

            var presencaBadge = '';
            if(props.presenca === 'presente') presencaBadge = '<span class="badge bg-success">Presente</span>';
            else if(props.presenca === 'ausente') presencaBadge = '<span class="badge bg-warning">Ausente</span>';
            else if(props.presenca === 'justificada') presencaBadge = '<span class="badge bg-info">Justificada</span>';
            else presencaBadge = '<span class="badge bg-secondary">Não marcada</span>';
            document.getElementById('eventPresenca').innerHTML = presencaBadge;

            document.getElementById('eventObs').innerText = props.observacoes || '-';

            // WhatsApp Button
            var btnWa = document.getElementById('btnWhatsapp');
            if (props.whatsapp) {
                var waNum = props.whatsapp.replace(/\D/g, '');
                if (waNum.length <= 11) waNum = '55' + waNum;
                var msg = "Olá " + (props.aluno_nome || '') + ", confirmando nossa aula de " + event.title + " dia " + event.start.toLocaleDateString('pt-BR') + " às " + event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) + ".";
                btnWa.href = "https://wa.me/" + waNum + "?text=" + encodeURIComponent(msg);
                btnWa.classList.remove('d-none');
            } else {
                btnWa.classList.add('d-none');
            }
            
            // Edit/Delete Buttons
            document.getElementById('btnEdit').href = 'agenda_cadastro.php?id=' + event.id;
            document.getElementById('btnDelete').href = 'agenda_excluir.php?id=' + event.id;

            document.getElementById('btnEdit').onclick = function() {
                window.location.href = 'agenda_cadastro.php?id=' + event.id;
            };

            // Attendance List Loading
            var attendanceSection = document.getElementById('attendanceSection');
            if (props.status !== 'cancelado') {
                attendanceSection.classList.remove('d-none');
                loadAttendanceList(event.id);
            } else {
                attendanceSection.classList.add('d-none');
            }

            // Show Modal
            var modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        },
        eventTimeFormat: { // like '14:30'
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false
        }
    });
    calendar.render();

    // Load Attendance List
    window.loadAttendanceList = function(agendaId) {
        var listContainer = document.getElementById('attendanceList');
        var btnSave = document.getElementById('btnSaveAttendance');
        
        listContainer.innerHTML = '<div class="text-center text-muted py-2"><i class="fas fa-spinner fa-spin"></i> Carregando lista...</div>';
        btnSave.classList.add('d-none');

        fetch('ajax_lista_chamada.php?agenda_id=' + agendaId)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                var html = '<div class="list-group">';
                if (data.alunos.length === 0) {
                     html += '<div class="list-group-item text-center text-muted">Nenhum aluno vinculado.</div>';
                } else {
                    data.alunos.forEach(aluno => {
                        var p = aluno.status_presenca;
                        var name = 'presenca_' + aluno.aluno_id;
                        
                        html += '<div class="list-group-item d-flex justify-content-between align-items-center p-2">';
                        html += '<div>' + aluno.nome + '</div>';
                        html += '<div class="btn-group btn-group-sm" role="group">';
                        
                        html += '<input type="radio" class="btn-check" name="'+name+'" id="'+name+'_p" value="presente" '+(p==='presente'?'checked':'')+'>';
                        html += '<label class="btn btn-outline-success" for="'+name+'_p" title="Presente">P</label>';
                        
                        html += '<input type="radio" class="btn-check" name="'+name+'" id="'+name+'_a" value="ausente" '+(p==='ausente'?'checked':'')+'>';
                        html += '<label class="btn btn-outline-danger" for="'+name+'_a" title="Ausente">A</label>';
                        
                        html += '<input type="radio" class="btn-check" name="'+name+'" id="'+name+'_j" value="justificada" '+(p==='justificada'?'checked':'')+'>';
                        html += '<label class="btn btn-outline-info" for="'+name+'_j" title="Justificada">J</label>';
                        
                        html += '</div></div>';
                    });
                }
                html += '</div>';
                listContainer.innerHTML = html;
                btnSave.classList.remove('d-none');
            } else {
                listContainer.innerHTML = '<div class="alert alert-danger p-2">' + (data.error || 'Erro ao carregar') + '</div>';
            }
        })
        .catch(err => {
            console.error(err);
            listContainer.innerHTML = '<div class="alert alert-danger p-2">Erro de conexão</div>';
        });
    };

    // Save Attendance
    window.salvarChamada = function() {
        var agendaId = window.currentEventId;
        var inputs = document.querySelectorAll('#attendanceList input[type="radio"]:checked');
        var presencas = [];
        
        inputs.forEach(input => {
            var alunoId = input.name.split('_')[1];
            presencas.push({
                aluno_id: alunoId,
                status: input.value
            });
        });
        
        if(presencas.length === 0) {
            alert('Nenhuma presença marcada.');
            return;
        }

        var btn = document.getElementById('btnSaveAttendance');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        fetch('ajax_lista_chamada.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                agenda_id: agendaId,
                presencas: presencas
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Chamada salva com sucesso!');
                calendar.refetchEvents();
                // Close modal or reload list
                loadAttendanceList(agendaId);
            } else {
                alert('Erro: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(err => {
            alert('Erro de conexão');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
});
</script>

<style>
/* FullCalendar Customization */
.fc-event {
    cursor: pointer;
}
.fc-toolbar-title {
    font-size: 1.2rem !important;
}
@media (max-width: 768px) {
    .fc-toolbar {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
