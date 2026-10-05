{#
 # Fields of the <?= $resource->snake ?> form, shared by new.html.twig and edit.html.twig.
 # Tabs: General (every field but the translations), Translations (language tabs).
 # A tab with errors is red with an alert icon, the first invalid one is open.
 #}
{% set general_fields = [] %}
{% for child in form|filter(child => child.vars.name != 'translations' and not child.rendered) %}
    {% set general_fields = general_fields|merge([child]) %}
{% endfor %}
{% set general_valid = general_fields|filter(child => not child.vars.valid) is empty %}
{% set tabs = [
    {id: 'general', label: '<?= $resource->snake ?>.tab.general'|trans({}, 'admin'), valid: general_valid},
    {id: 'translations', label: '<?= $resource->snake ?>.field.translations'|trans({}, 'admin'), valid: form.translations.vars.valid},
] %}
{% set active = general_valid and not form.translations.vars.valid ? 'translations' : 'general' %}

<nav class="mb-3">
    <div class="nav nav-tabs" role="tablist">
        {% for tab in tabs %}
            <button type="button" class="nav-link{{ tab.id == active ? ' active' }}{{ tab.valid ? '' : ' text-danger' }}"
                    id="<?= $resource->snake ?>-tab-{{ tab.id }}" data-bs-toggle="tab" data-bs-target="#<?= $resource->snake ?>-pane-{{ tab.id }}"
                    role="tab" aria-controls="<?= $resource->snake ?>-pane-{{ tab.id }}" aria-selected="{{ tab.id == active ? 'true' : 'false' }}">
                {{- tab.label -}}
                {%- if not tab.valid %} <i class="bi bi-exclamation-circle" aria-hidden="true"></i>{% endif -%}
            </button>
        {% endfor %}
    </div>
</nav>

<div class="tab-content">
    <div class="tab-pane fade{{ active == 'general' ? ' show active' }}" id="<?= $resource->snake ?>-pane-general"
         role="tabpanel" aria-labelledby="<?= $resource->snake ?>-tab-general" tabindex="0">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    {% for child in general_fields %}
                        {{ form_row(child) }}
                    {% endfor %}
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade{{ active == 'translations' ? ' show active' }}" id="<?= $resource->snake ?>-pane-translations"
         role="tabpanel" aria-labelledby="<?= $resource->snake ?>-tab-translations" tabindex="0">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                {{ form_widget(form.translations) }}
                {{ form_errors(form.translations) }}
            </div>
        </div>
    </div>
</div>
