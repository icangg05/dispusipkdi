/**
 * Site header: the mobile menu drawer and the satisfaction survey dialog.
 * The survey iframe loads on the first open and stays loaded, so closing the dialog keeps the answers.
 */
export default () => ({
    open: false,
    survey: false,
    surveyLoaded: false,
    trigger: null,

    openSurvey() {
        this.trigger = document.activeElement;
        this.open = false;
        this.survey = this.surveyLoaded = true;
        document.documentElement.style.overflow = 'hidden';
        this.$nextTick(() => this.$refs.closeSurvey?.focus());
    },

    closeSurvey() {
        if (!this.survey) return;
        this.survey = false;
        document.documentElement.style.overflow = '';
        this.trigger?.focus?.();
    },

    destroy() {
        if (this.survey) document.documentElement.style.overflow = '';
    },
});
