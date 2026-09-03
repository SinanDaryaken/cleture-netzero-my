export type SharedPageProps = {
    auth: {
        user: {
            id: string;
            name: string;
            email: string;
            emailVerified: boolean;
        } | null;
    };
    flash: {
        status: string | null;
    };
    localization: {
        locale: string;
        languages: Array<{
            code: string;
            nativeName: string;
        }>;
        translations: UiTranslations;
    };
};

export type UiTranslations = {
    common: {
        email: string;
        password: string;
        backToLogin: string;
        accountRecovery: string;
        preparingRequest: string;
    };
    authLayout: {
        homeLabel: string;
        brandKicker: string;
        brandCopy: string;
        trustNote: string;
        selectLanguage: string;
    };
    appLayout: {
        dashboardLabel: string;
        navigationLabel: string;
        overview: string;
        organization: string;
        collapseSidebar: string;
        expandSidebar: string;
        logout: string;
    };
    login: {
        headTitle: string;
        eyebrow: string;
        title: string;
        description: string;
        forgotPassword: string;
        submit: string;
        processing: string;
        noAccount: string;
        register: string;
    };
    register: {
        headTitle: string;
        eyebrow: string;
        title: string;
        description: string;
        name: string;
        passwordConfirmation: string;
        note: string;
        submit: string;
        processing: string;
        hasAccount: string;
        login: string;
    };
    forgotPassword: {
        headTitle: string;
        title: string;
        description: string;
        status: string;
        submit: string;
        rememberedPassword: string;
    };
    resetPassword: {
        headTitle: string;
        title: string;
        description: string;
        newPassword: string;
        passwordHint: string;
        passwordConfirmation: string;
        invalidLink: string;
        submit: string;
        processing: string;
    };
    verifyEmail: {
        headTitle: string;
        eyebrow: string;
        title: string;
        emailFallback: string;
        description: string;
        submit: string;
        note: string;
        otherAccount: string;
        logout: string;
    };
    dashboard: {
        headTitle: string;
        eyebrow: string;
        title: string;
        description: string;
        accountStatus: string;
        emailVerified: string;
        verifiedDescription: string;
        nextStep: string;
        organizationSetup: string;
        organizationDescription: string;
        manageOrganization: string;
    };
    organization: {
        headTitle: string;
        backToDashboard: string;
        eyebrow: string;
        title: string;
        description: string;
        existingLabel: string;
        newLabel: string;
        formTitle: string;
        name: string;
        taxNumber: string;
        taxNumberHint: string;
        createSubmit: string;
        creating: string;
        updateSubmit: string;
        updating: string;
        created: string;
        updated: string;
    };
};
