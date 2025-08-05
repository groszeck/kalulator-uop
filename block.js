function initCalculatorAssets() {
    if (! window.kpjPracownikKalkulatorAssetsLoading) {
        window.kpjPracownikKalkulatorAssetsLoading = new Promise((resolve, reject) => {
            if (
                window.KPJ_Pracownik_Kalkulator &&
                typeof window.KPJ_Pracownik_Kalkulator.initAssets === 'function'
            ) {
                try {
                    const result = window.KPJ_Pracownik_Kalkulator.initAssets();
                    if (result && typeof result.then === 'function') {
                        result.then(resolve).catch(reject);
                    } else {
                        resolve();
                    }
                } catch (e) {
                    reject(e);
                }
            } else {
                reject(new Error('KPJ_Pracownik_Kalkulator.initAssets not found'));
            }
        });
    }
    return window.kpjPracownikKalkulatorAssetsLoading;
}

function Edit() {
    const blockProps = useBlockProps();
    const containerRef = useRef();
    useEffect(() => {
        let mounted = true;
        let instance = null;
        initCalculatorAssets()
            .then(() => {
                if (! mounted) {
                    return;
                }
                if (
                    containerRef.current &&
                    window.KPJ_Pracownik_Kalkulator &&
                    typeof window.KPJ_Pracownik_Kalkulator.render === 'function'
                ) {
                    instance = window.KPJ_Pracownik_Kalkulator.render(containerRef.current);
                }
            })
            .catch((err) => {
                // eslint-disable-next-line no-console
                console.error('KPJ Kalkulator asset load error:', err);
            });
        return () => {
            mounted = false;
            if (instance && typeof instance.destroy === 'function') {
                instance.destroy();
            } else if (containerRef.current) {
                containerRef.current.innerHTML = '';
            }
        };
    }, []);
    return (
        <div {...blockProps}>
            <div
                ref={containerRef}
                className="kpj-pracownik-kalkulator-block"
            />
        </div>
    );
}

function Save() {
    const blockProps = useBlockProps.save();
    return (
        <div {...blockProps}>
            <div className="kpj-pracownik-kalkulator-block" />
        </div>
    );
}

registerBlockType('kpj/pracownik-kalkulator', {
    apiVersion: 2,
    title: __('KPJ Pracownik Kalkulator', 'kpj-pracownik-kalkulator'),
    description: __(
        'Kalkulator wynagrodze? brutto ? netto do osadzania na stronach WordPress.',
        'kpj-pracownik-kalkulator'
    ),
    icon: 'calculator',
    category: 'widgets',
    keywords: [
        __('kalkulator', 'kpj-pracownik-kalkulator'),
        __('wynagrodzenie', 'kpj-pracownik-kalkulator'),
        __('brutto netto', 'kpj-pracownik-kalkulator'),
    ],
    supports: { html: false },
    edit: Edit,
    save: Save,
});