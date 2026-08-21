let allState = document.getElementById('state');
let allCity = document.getElementById('city');
let stateCities = {
    Rajasthan: ['Jodhpur', 'Jaipur', 'Ajmer', 'Udaipur'],
    Haryana: ['Faridabad', 'Rohtak', 'Panipat', 'Thanesar'],
    Gujrat: ['Ahemdabad', 'Surat', 'Anand', 'Dwarka', 'Somnath'],
    Punjab: ['Ludhiana', 'Amritsar', 'Jalandhar', 'Mohali'],
};

allState.addEventListener('change', () => {
    let state = allState.options[allState.selectedIndex].value;
    const deleteCity = (allCity) => {
        if (allCity.options.length > 0) {
            allCity.options.length = 0;
        }
    }

    deleteCity(allCity);

    const showingState = (state, obj) => {
        obj[state].forEach(element => {
                    let el = document.createElement("option");
                    el.textContent = element;
                    el.value = element;
                    allCity.appendChild(el);
                });
    }

    showingState(state, stateCities);
})