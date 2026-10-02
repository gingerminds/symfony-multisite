{# Fields of the <?= $resource->snake ?> form, shared by new.html.twig and edit.html.twig. #}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            {% for child in form|filter(child => child.vars.name != 'translations' and not child.rendered) %}
                {{ form_row(child) }}
            {% endfor %}
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3">
        <h5 class="card-title mb-0"><i class="bi bi-translate me-1 text-primary"></i> {{ '<?= $resource->snake ?>.field.translations'|trans({}, 'admin') }}</h5>
    </div>
    <div class="card-body">
        {{ form_widget(form.translations) }}
    </div>
</div>
