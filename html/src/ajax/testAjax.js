/**
 * Created by lengbin on 2017/7/12.
 */

import Base from './base.js';

export default {
    testWorkflowFormValidate(body) {
        return Base({
            url: '/test/form-validate',
            body: body
        });
    },
    testWorkflowName(body) {
        return Base({
            url: '/test/test-workflow-name',
            body: body
        });
    },
    testWorkflowUpdate(body) {
        return Base({
            url: '/test/update',
            body: body,
            method: 'post'
        });
    },
    generateCase(body) {
        return Base({
            url: '/test/generate-case',
            body: body,
            method: 'get'
        });
    },
    changeRunStatus(body) {
        return Base({
            url: '/test/run',
            body: body,
            method: 'get'
        });
    }
};
