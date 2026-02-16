@php
    $receivedDate = $agreement->documents_received_at?->format('d/m/Y H:i');
    $isSent = !is_null($receivedDate);
@endphp

@if($isSent)
<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem; font-family: sans-serif;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
        <div style="background-color: #dcfce7; color: #15803d; padding: 0.375rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #14532d; line-height: 1; margin: 0;">Estatus: Recepción de Documentos</h3>
            <p style="font-size: 0.875rem; color: #15803d; margin: 0.25rem 0 0 0;">Convenio completado y notificación enviada</p>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; row-gap: 1rem;">
        <div style="flex: 1; min-width: 140px;">
            <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #166534; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 4px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Enviado el
            </span>
            <span style="display: block; font-size: 1rem; font-weight: 600; color: #111827;">{{ $receivedDate }}</span>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #166534; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 4px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                Destinatario
            </span>
            <span style="display: block; font-size: 1rem; font-weight: 600; color: #111827;">{{ $clientEmail }}</span>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #166534; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 4px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Enviado por
            </span>
            <span style="display: block; font-size: 1rem; font-weight: 600; color: #111827;">{{ $advisorEmail }}</span>
        </div>
        
        <div style="width: 100%; height: 1px; background-color: #bbf7d0;"></div>

        <div style="flex: 1; min-width: 250px;">
             <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #166534; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 4px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Estado
            </span>
            <span style="display: block; font-size: 0.95rem; color: #14532d;">Documentación recibida y convenio completado.</span>
        </div>

         <div style="flex: 1; min-width: 250px;">
             <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #166534; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 4px;">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Etapa Actual
            </span>
            <span style="display: block; font-size: 0.95rem; font-weight: 700; color: #14532d;">Proceso de Documentación Finalizado</span>
        </div>
    </div>
</div>
@else
<div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem; font-family: sans-serif;">
     <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <div style="background-color: #fee2e2; color: #b91c1c; padding: 0.375rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #991b1b; line-height: 1; margin: 0;">Estatus: Recepción Pendiente</h3>
        </div>
    </div>
    <p style="font-size: 0.875rem; color: #7f1d1d; margin: 0; line-height: 1.5;">
        Este correo de confirmación final se enviará automáticamente al cliente una vez que se complete la carga de todos los documentos en este paso y se avance al Paso 3.
    </p>
</div>
@endif
