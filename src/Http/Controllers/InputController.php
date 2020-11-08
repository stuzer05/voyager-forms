<?php

namespace Pvtl\VoyagerForms\Http\Controllers;

use Pvtl\VoyagerForms\Form;
use Illuminate\Http\Request;
use Pvtl\VoyagerForms\FormInput;
use Pvtl\VoyagerForms\Traits\DataType;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class InputController extends VoyagerBaseController
{
    use DataType;

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('add', app(FormInput::class));

        $dataType = $this->getDataType($request);
        $form = Form::findOrFail($request->input('form_id'));

        $form->inputs()->create($request->all())->save();

        return redirect()
            ->back()
            ->with([
                'message' => __('voyager::generic.successfully_added_new') . " {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, $id)
    {
        $this->authorize('edit', app(FormInput::class));

        $formInput = FormInput::findOrFail($id);
        $dataType = $this->getDataType($request);

        $translatable_fields = ['label', 'options'];
        $translations = $this->prepareTranslations($translatable_fields, $request);

        $default_locale = config('voyager.multilingual.default', 'en');
        $locales = config('voyager.multilingual.locales', [$default_locale]);

        $data = $request->all();
        $formInput->fill($data);
        $formInput->required = $request->has('required');

        if (!empty($translations)) {
            foreach ($locales as $locale) {
                if (!array_key_exists($locale, $translations)) continue;

                $localized_data = $data;
                foreach ($translations[$locale] as $field => $translation) {
                    $localized_data[$field] = $translation;
                }

                if ($locale == $default_locale) {
                    $formInput->label = $localized_data['label'] ?? '';
                    $formInput->options = $localized_data['options'] ?? '';

                    $formInput->save();
                } else {
                    $formInput_trans = $formInput->translate($locale);

                    $formInput_trans->label = $localized_data['label'] ?? '';
                    $formInput_trans->options = $localized_data['options'] ?? '';

                    $formInput_trans->save();
                }
            }
        } else {
            $formInput->save();
        }

        return redirect()
            ->back()
            ->with([
                'message' => __('voyager::generic.successfully_updated') . " {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', app(FormInput::class));

        $formInput = FormInput::findOrFail($id);
        $dataType = $this->getDataType($request);

        $formInput->delete();

        return redirect()
            ->back()
            ->with([
                'message' => __('voyager::generic.successfully_deleted') . " {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    /**
     * POST - Put inputs into order
     *
     * @param \Illuminate\Http\Request $request
     */
    public function sort(Request $request)
    {
        $inputOrder = json_decode($request->input('order'));

        foreach ($inputOrder as $index => $item) {
            $input = FormInput::findOrFail($item->id);
            $input->order = $index + 1;
            $input->save();
        }
    }

    public function prepareTranslations($fields, $request)
    {
        $translations = [];

        $default_locale = config('voyager.multilingual.default', 'en');
        $locales = config('voyager.multilingual.locales', [$default_locale]);

        // $fields = !empty($request->attributes->get('breadRows')) ? array_intersect($request->attributes->get('breadRows'), $transFields) : $transFields;

        foreach ($fields as $field) {
            if (!$request->input($field.'_i18n')) {
                continue;
                // throw new Exception('Invalid Translatable field'.$field);
            }

            $trans = json_decode($request->input($field.'_i18n'), true);

            foreach ($trans as $lang => $translation) {
                if (!array_key_exists($lang, $translations)) $translations[$lang] = [];
                $translations[$lang][$field] = $translation;
            }

            // Set the default local value
            $request->merge([$field => $trans[config('voyager.multilingual.default', 'en')]]);

            // Remove field hidden input
            unset($request[$field.'_i18n']);
        }

        // Remove language selector input
        unset($request['i18n_selector']);

        return $translations;
    }
}
