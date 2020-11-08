<?php

namespace Pvtl\VoyagerForms\Http\Controllers;

use Illuminate\Http\Request;
use Pvtl\VoyagerForms\Form;
use Pvtl\VoyagerForms\FormInput;
use Pvtl\VoyagerForms\Traits\DataType;
use Pvtl\VoyagerFrontend\Helpers\Layouts;
use Pvtl\VoyagerForms\Validators\FormValidators;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class FormController extends VoyagerBaseController
{
    use DataType;

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request)
    {
        $this->authorize('add', app(Form::class));

        return view('voyager-forms::forms.edit-add', [
            'dataType' => $this->getDataType($request),
            'layouts' => Layouts::getLayouts('voyager-forms'),
            'emailTemplates' => Layouts::getLayouts('voyager-forms', 'email-templates'),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('add', app(Form::class));

        $dataType = $this->getDataType($request);

        if ($request->input('hook')) {
            $validator = FormValidators::validateHook($request);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with([
                        'message' => __('voyager::json.validation_errors'),
                        'alert-type' => 'error',
                    ]);
            }
        }

        $translatable_fields = ['title', 'message_success'];
        $translations = $this->prepareTranslations($translatable_fields, $request);

        $default_locale = config('voyager.multilingual.default', 'en');
        $locales = config('voyager.multilingual.locales', [$default_locale]);

        // Create the form
        $data = $request->all();
        $data['title'] = '';
        $data['message_success'] = '';
        $form = new Form($data);
        $form->save();

        $data = $request->all();

        if (!empty($translations)) {
            foreach ($locales as $locale) {
                if (!array_key_exists($locale, $translations)) continue;

                $localized_data = $data;
                foreach ($translations[$locale] as $field => $translation) {
                    $localized_data[$field] = $translation;
                }

                if ($locale == $default_locale) {
                    $form->title = $localized_data['title'];
                    $form->message_success = $localized_data['message_success'] ?? '';

                    $form->save();
                } else {
                    $form_trans = $form->translate($locale);

                    $form_trans->title = $localized_data['title'];
                    $form_trans->message_success = $localized_data['message_success'] ?? '';

                    $form_trans->save();
                }
            }
        } else {
            $form->save();
        }

        // Create some default inputs
        $inputs = [
            'name' => 'text',
            'email' => 'email',
            'phone' => 'text',
            'message' => 'text_area',
        ];
        $order = 1;
        foreach ($inputs as $key => $value) {
            FormInput::create([
                'form_id' => $form->id,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'type' => $value,
                'required' => 1,
                'order' => $order,
            ])->save();

            $order++;
        }

        return redirect()
            ->route('voyager.forms.edit', ['id' => $form->id])
            ->with([
                'message' => __('voyager::generic.successfully_added_new') . " {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, $id)
    {
        $this->authorize('read', app(Form::class));

        $form = Form::findOrFail($id);

        return view('voyager-forms::forms.edit-add', [
            'form' => $form,
            'layouts' => Layouts::getLayouts('voyager-forms'),
            'emailTemplates' => Layouts::getLayouts('voyager-forms', 'email-templates'),
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request, $id)
    {
        $this->authorize('edit', app(Form::class));

        $form = Form::findOrFail($id);

        return view('voyager-forms::forms.edit-add', [
            'dataType' => $this->getDataType($request),
            'form' => $form,
            'layouts' => Layouts::getLayouts('voyager-forms'),
            'emailTemplates' => Layouts::getLayouts('voyager-forms', 'email-templates'),
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
        $this->authorize('edit', app(Form::class));

        $dataType = $this->getDataType($request);
        $form = Form::findOrFail($id);

        if ($request->input('hook')) {
            $validator = FormValidators::validateHook($request);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with([
                        'message' => __('voyager::json.validation_errors'),
                        'alert-type' => 'error',
                    ]);
            }
        }

        $translatable_fields = ['title', 'message_success'];
        $translations = $this->prepareTranslations($translatable_fields, $request);

        $default_locale = config('voyager.multilingual.default', 'en');
        $locales = config('voyager.multilingual.locales', [$default_locale]);

        $data = $request->all();
        $form->fill($data);

        if (!empty($translations)) {
            foreach ($locales as $locale) {
                if (!array_key_exists($locale, $translations)) continue;

                $localized_data = $data;
                foreach ($translations[$locale] as $field => $translation) {
                    $localized_data[$field] = $translation;
                }

                if ($locale == $default_locale) {
                    $form->title = $localized_data['title'];
                    $form->message_success = $localized_data['message_success'] ?? '';

                    $form->save();
                } else {
                    $form_trans = $form->translate($locale);

                    $form_trans->title = $localized_data['title'];
                    $form_trans->message_success = $localized_data['message_success'] ?? '';

                    $form_trans->save();
                }
            }
        } else {
            $form->save();
        }

        return redirect()
            ->back()
            ->with([
                'message' => __('voyager::generic.successfully_updated') . " {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    public function prepareTranslations($fields, $request)
    {
        $translations = [];

        $default_locale = config('voyager.multilingual.default', 'en');
        $locales = config('voyager.multilingual.locales', [$default_locale]);

        // $fields = !empty($request->attributes->get('breadRows')) ? array_intersect($request->attributes->get('breadRows'), $transFields) : $transFields;

        foreach ($fields as $field) {
            if (!$request->input($field.'_i18n')) {
                throw new Exception('Invalid Translatable field'.$field);
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
