

(function(mobile){
    window.LtiCustom = {
        mobile : null,

        launch: async (mobile) => {
            LtiCustom.mobile = mobile;
            const string =  "[string_form_lticustom]";
            const form = atob(string);
            const url = await LtiCustom.writeFile(form);
            return LtiCustom.mobile.CoreUtilsProvider.openInApp(url);

        },

         writeFile: async (form) => {
            const fileInfo = await LtiCustom.mobile.CoreFileProvider.writeFile('lticustom_launcher.html', form);
            if (mobile.CoreAppProvider.isDesktop()) {
                return fileInfo.toInternalURL();
            }
            return fileInfo.toURL();
        }
    }

    try{
        LtiCustom.launch(mobile);
    }catch (e) {

    }

})(this);
