<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Minha Agenda';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-calendar-alt me-2"></i> Minha Agenda</h1>
    <a href="agenda_cadastro.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Novo Agendamento</a>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Detalhes do Evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventTitle">Detalhes do Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Horário:</strong> <span id="eventTime"></span></p>
                <p><strong>Aluno:</strong> <span id="eventAluno"></span></p>
                <p><strong>Status:</strong> <span id="eventStatus"></span></p>
                <p><strong>Presença:</strong> <span id="eventPresenca"></span></p>
                <p><strong>Observações:</strong> <span id="eventObs"></span></p>

                <!-- Attendance Controls -->
                <div id="attendanceControls" class="mt-3 d-none">
                    <h6>Controle de Presença:</h6>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-success btn-sm" onclick="marcarPresenca('presente')">Presente</button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="marcarPresenca('ausente')">Ausente</button>
                        <button type="button" class="btn btn-info btn-sm" onclick="marcarPresenca('justificada')">Justificada</button>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a id="btnWhatsapp" href="#" target="_blank" class="btn btn-success d-none">
                        <i class="fab fa-whatsapp me-2"></i> Confirmar no WhatsApp
                    </a>
                    <div class="row g-2">
                        <div class="col-6">
                            <a id="btnEdit" href="#" class="btn btn-outline-primary w-100">
                                <i class="fas fa-edit me-2"></i> Editar
                            </a>
                        </div>
                        <div class="col-6">
                            <a id="btnDelete" href="#" class="btn btn-outline-danger w-100" onclick="return confirm('Tem certeza que deseja excluir?');">
                                <i class="fas fa-trash me-2"></i> Excluir
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
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia',
            list: 'Lista'
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
            
            document.getElementById('eventAluno').innerText = props.aluno_nome || 'N/A';

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

            // Show attendance controls if event is in the past or can be marked
            var now = new Date();
            var eventEnd = event.end || event.start;
            var attendanceControls = document.getElementById('attendanceControls');
            if (eventEnd < now && props.status !== 'cancelado') {
                attendanceControls.classList.remove('d-none');
            } else {
                attendanceControls.classList.add('d-none');
            }
            
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

            // Store event data for attendance marking
            document.getElementById('btnEdit').onclick = function() {
                window.location.href = 'agenda_cadastro.php?id=' + event.id;
            };

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

    // Function to mark attendance
    window.marcarPresenca = function(status) {
        var eventId = window.currentEventId;
        var modal = bootstrap.Modal.getInstance(document.getElementById('eventModal'));

        if (confirm('Deseja marcar presença como ' + status + '?')) {
            fetch('agenda_marcar_presenca.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + encodeURIComponent(eventId) + '&presenca=' + encodeURIComponent(status)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the badge in modal
                    var presencaBadge = '';
                    if(status === 'presente') presencaBadge = '<span class="badge bg-success">Presente</span>';
                    else if(status === 'ausente') presencaBadge = '<span class="badge bg-warning">Ausente</span>';
                    else if(status === 'justificada') presencaBadge = '<span class="badge bg-info">Justificada</span>';
                    document.getElementById('eventPresenca').innerHTML = presencaBadge;

                    // Hide controls after marking
                    document.getElementById('attendanceControls').classList.add('d-none');

                    // Refresh calendar
                    calendar.refetchEvents();

                    alert('Presença marcada com sucesso!');
                } else {
                    alert('Erro ao marcar presença: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao marcar presença');
            });
        }
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
